<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\StockLog;
use App\Support\AdminCache;
use App\Support\IngredientStockView;
use App\Support\IngredientUnit;
use App\Support\StockLogView;
use App\Services\ReportExportDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $categoriesQuery = IngredientCategory::with([
            'ingredients' => function ($query) use ($request) {
                if ($request->filled('search')) {
                    $this->applyNameSearch($query, $request->search);
                }

                if ($request->filled('has_price')) {
                    if ($request->has_price === '1') {
                        $query->where('selling_price', '>', 0);
                    } elseif ($request->has_price === '0') {
                        $query->where(function ($q) {
                            $q->whereNull('selling_price')->orWhere('selling_price', '<=', 0);
                        });
                    }
                }

                $query->orderBy('name');
            }
        ]);

        if ($request->filled('category')) {
            $categoriesQuery->where('id', $request->category);
        }

        $categories = $categoriesQuery
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories->setCollection(
            $categories->getCollection()->map(function (IngredientCategory $category) {
                $ingredients = $category->ingredients->map(function (Ingredient $ingredient) {
                    $ingredient->stock_meta = IngredientStockView::fromIngredient($ingredient);
                    return $ingredient;
                });

                $category->setRelation('ingredients', $ingredients);

                $category->stock_summary = [
                    'out' => $ingredients->filter(fn (Ingredient $ingredient) => (bool) ($ingredient->stock_meta['is_out'] ?? false))->count(),
                    'low' => $ingredients->filter(fn (Ingredient $ingredient) => (bool) ($ingredient->stock_meta['is_low'] ?? false))->count(),
                ];

                return $category;
            })
        );

        $allCategories = Cache::remember(
            AdminCache::key('stock', 'all_categories:with_counts'),
            now()->addSeconds(120),
            function () {
                return IngredientCategory::query()
                    ->withCount('ingredients')
                    ->withCount([
                        'ingredients as out_of_stock_count' => function ($query) {
                            $query->where('stock', '<=', 0);
                        },
                        'ingredients as low_stock_count' => function ($query) {
                            $query->where('stock', '>', 0)
                                ->whereColumn('stock', '<=', 'minimum_stock');
                        },
                    ])
                    ->orderBy('name')
                    ->get()
                    ->map(function (IngredientCategory $category) {
                        $outCount = (int) ($category->out_of_stock_count ?? 0);
                        $lowCount = (int) ($category->low_stock_count ?? 0);

                        $marker = '';
                        if ($outCount > 0 || $lowCount > 0) {
                            $marker = '(H:' . $outCount . ' R:' . $lowCount . ')';
                        }

                        $category->status_marker = $marker;

                        return $category;
                    });
            }
        );

        $lowStockQuery = Ingredient::query()
            ->whereColumn('stock', '<=', 'minimum_stock');

        if ($request->filled('search')) {
            $this->applyNameSearch($lowStockQuery, $request->search);
        }

        if ($request->filled('category')) {
            $lowStockQuery->where('category_id', $request->category);
        }

        if ($request->filled('has_price')) {
            if ($request->has_price === '1') {
                $lowStockQuery->where('selling_price', '>', 0);
            } elseif ($request->has_price === '0') {
                $lowStockQuery->where(function ($q) {
                    $q->whereNull('selling_price')->orWhere('selling_price', '<=', 0);
                });
            }
        }

        $lowStockCount = Cache::remember(
            AdminCache::key('stock', 'low_stock_count:' . md5(json_encode([
                'search' => (string) $request->input('search', ''),
                'category' => (string) $request->input('category', ''),
                'has_price' => (string) $request->input('has_price', ''),
            ]))),
            now()->addSeconds(60),
            fn () => $lowStockQuery->count()
        );

        return view(
            'admin.stocks.index',
            compact(
                'categories',
                'allCategories',
                'lowStockCount'
            )
        );
    }

    public function restockForm(Ingredient $ingredient)
    {
        return view('admin.stocks.restock', compact('ingredient'));
    }

    public function restock(Request $request, Ingredient $ingredient)
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric|min:0.01',
                'note' => 'nullable|string|max:255'
            ]);

            $quantityInBaseUnit = $this->normalizeQuantityForIngredient($ingredient, (float) $request->quantity);

            DB::transaction(function () use ($request, $ingredient, $quantityInBaseUnit) {
                $ingredient->increment('stock', $quantityInBaseUnit);

                StockLog::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'in',
                    'quantity' => $quantityInBaseUnit,
                    'note' => $request->note
                ]);
            });

            AdminCache::bumpDashboard();
            AdminCache::bumpStock();

            return redirect()
                ->route('admin.stocks.index')
                ->with('success', 'Restok berhasil dilakukan.');
        } catch (\Throwable $e) {
            Log::error('Gagal restok bahan', [
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat restok. Coba lagi.');
        }
    }

    public function adjustForm(Ingredient $ingredient)
    {
        return view('admin.stocks.adjust', compact('ingredient'));
    }

    public function adjust(Request $request, Ingredient $ingredient)
    {
        try {
            $request->validate([
                'new_stock' => 'required|numeric|min:0',
                'note' => 'required|string|max:255'
            ]);

            $newStockInBaseUnit = $this->normalizeQuantityForIngredient($ingredient, (float) $request->new_stock);

            if (round($newStockInBaseUnit, 2) === round((float) $ingredient->stock, 2)) {
                return back()
                    ->withInput()
                    ->with('error', 'Stok baru sama dengan stok saat ini. Tidak ada perubahan yang disimpan.');
            }

            DB::transaction(function () use ($request, $ingredient, $newStockInBaseUnit) {
                $difference = $newStockInBaseUnit - $ingredient->stock;

                $ingredient->update([
                    'stock' => $newStockInBaseUnit
                ]);

                StockLog::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'note' => $request->note
                ]);
            });

            AdminCache::bumpDashboard();
            AdminCache::bumpStock();

            return redirect()
                ->route('admin.stocks.index')
                ->with('success', 'Penyesuaian stok berhasil.');
        } catch (\Throwable $e) {
            Log::error('Gagal penyesuaian stok', [
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat penyesuaian stok. Coba lagi.');
        }
    }

    public function logs(Request $request)
    {
        $period = StockLogView::normalizePeriod($request->input('period'));
        $selectedDate = StockLogView::parseSelectedDate($request->input('date'));
        [$rangeStart, $rangeEnd] = StockLogView::resolveRange($period, $selectedDate);

        $logsQuery = StockLog::with('ingredient')->latest();
        $logsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);

        $typeFilter = $request->input('type');
        if ($typeFilter && in_array($typeFilter, ['in', 'out', 'adjustment'], true)) {
            $logsQuery->where('type', $typeFilter);
        }

        $summary = Cache::remember(
            AdminCache::key('stock', 'logs_summary:' . md5(json_encode([
                'period' => $period,
                'date' => $selectedDate->toDateString(),
                'type' => (string) $typeFilter,
            ]))),
            now()->addSeconds(60),
            function () use ($rangeStart, $rangeEnd, $typeFilter) {
                $summaryBaseQuery = StockLog::query()
                    ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

                if ($typeFilter && in_array($typeFilter, ['in', 'out', 'adjustment'], true)) {
                    $summaryBaseQuery->where('type', $typeFilter);
                }

                $row = $summaryBaseQuery
                    ->selectRaw(
                        'COUNT(*) as total,
                         SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as restock,
                         SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as usage,
                         SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as adjustment',
                        ['in', 'out', 'adjustment']
                    )
                    ->first();

                return [
                    'total' => (int) ($row->total ?? 0),
                    'restock' => (int) ($row->restock ?? 0),
                    'usage' => (int) ($row->usage ?? 0),
                    'adjustment' => (int) ($row->adjustment ?? 0),
                ];
            }
        );

        $summaryCards = StockLogView::summaryCards($summary);

        $logs = $logsQuery
            ->paginate(10)
            ->withQueryString();

        $logs->setCollection(
            $logs->getCollection()->map(fn (StockLog $log) => StockLogView::decorate($log))
        );

        $groupedLogs = $logs->getCollection()
            ->groupBy('group_date')
            ->map(function ($items) {
                /** @var StockLog $first */
                $first = $items->first();
                $groupLabel = $first->created_at->translatedFormat('d F Y');

                if ($first->created_at->isToday()) {
                    $groupLabel = 'Hari ini - ' . $groupLabel;
                } elseif ($first->created_at->isYesterday()) {
                    $groupLabel = 'Kemarin - ' . $groupLabel;
                }

                return [
                    'label' => $groupLabel,
                    'items' => $items,
                ];
            });

        $baseParams = array_filter([
            'period' => $period,
            'type' => $typeFilter,
        ], fn ($value) => $value !== null && $value !== '');

        $prevDate = StockLogView::navigationDate($period, $selectedDate, 'prev');
        $nextDate = StockLogView::navigationDate($period, $selectedDate, 'next');

        $prevParams = array_merge($baseParams, ['date' => $prevDate->toDateString()]);
        $nextParams = array_merge($baseParams, ['date' => $nextDate->toDateString()]);
        $isNextDisabled = $nextDate->startOfDay()->gt(now()->startOfDay());
        $dateDisplay = StockLogView::dateDisplay($period, $selectedDate, $rangeStart, $rangeEnd);
        $typeTabs = collect(StockLogView::typeTabs($typeFilter))
            ->map(function (array $tab) {
                $params = request()->query();

                if ($tab['key'] === null) {
                    unset($params['type']);
                } else {
                    $params['type'] = $tab['key'];
                }

                $tab['href'] = route('admin.stocks.logs', $params);
                return $tab;
            })
            ->values();

        return view('admin.stocks.logs', compact(
            'logs',
            'groupedLogs',
            'summary',
            'summaryCards',
            'period',
            'selectedDate',
            'rangeStart',
            'rangeEnd',
            'prevParams',
            'nextParams',
            'isNextDisabled',
            'dateDisplay',
            'typeFilter',
            'typeTabs'
        ));
    }

    public function exportLogs(Request $request)
    {
        try {
            $export = app(ReportExportDispatchService::class)->dispatch(
                $request->user(),
                'admin',
                'admin.stock_logs',
                $request->query()
            );

            $message = 'Export riwayat stok masuk antrian. ID: #' . $export->id;
            if ($export->scheduled_for) {
                $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
            }

            return redirect()
                ->route('admin.exports.index')
                ->with('success', $message);
        } catch (\Throwable) {
            return redirect()
                ->route('admin.exports.index')
                ->with('error', 'Export gagal diproses. Pastikan migrasi dan worker queue sudah aktif.');
        }
    }

    private function applyNameSearch($query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $query->where('name', 'ILIKE', "%{$search}%");
            return;
        }

        $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
    }

    private function normalizeQuantityForIngredient(Ingredient $ingredient, float $value): float
    {
        if ((string) $ingredient->display_unit === 'pcs') {
            return $value * max(1, (int) ($ingredient->pack_size ?? 1));
        }

        return IngredientUnit::toBase((string) $ingredient->display_unit, $value);
    }
}


