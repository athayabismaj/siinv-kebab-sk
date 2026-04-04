<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\StockLog;
use App\Support\IngredientStockView;
use App\Support\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $categoriesQuery = IngredientCategory::with([
            'ingredients' => function ($query) use ($request) {
                if ($request->filled('search')) {
                    $this->applyNameSearch($query, $request->search);
                }

                $query->orderBy('name');
            }
        ]);

        if ($request->filled('category')) {
            $categoriesQuery->where('id', $request->category);
        }

        $categories = $categoriesQuery
            ->orderBy('name')
            ->paginate(5)
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

        $allCategories = IngredientCategory::query()
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

        $lowStockQuery = Ingredient::query()
            ->whereColumn('stock', '<=', 'minimum_stock');

        if ($request->filled('search')) {
            $this->applyNameSearch($lowStockQuery, $request->search);
        }

        if ($request->filled('category')) {
            $lowStockQuery->where('category_id', $request->category);
        }

        $lowStockCount = $lowStockQuery->count();

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
        $period = $request->input('period', 'daily');
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        try {
            $selectedDate = $request->filled('date')
                ? Carbon::parse($request->input('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable $e) {
            $selectedDate = now()->startOfDay();
        }

        $rangeStart = null;
        $rangeEnd = null;
        if ($period === 'daily') {
            $rangeStart = $selectedDate->copy()->startOfDay();
            $rangeEnd = $selectedDate->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $rangeStart = $selectedDate->copy()->startOfWeek();
            $rangeEnd = $selectedDate->copy()->endOfWeek();
        } else {
            $rangeStart = $selectedDate->copy()->startOfMonth();
            $rangeEnd = $selectedDate->copy()->endOfMonth();
        }

        $logsQuery = StockLog::with('ingredient')->latest();
        $logsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);

        if ($request->filled('type') && in_array($request->type, ['in', 'out', 'adjustment'], true)) {
            $logsQuery->where('type', $request->type);
        }

        $summary = [
            'total' => (clone $logsQuery)->count(),
            'restock' => (clone $logsQuery)->where('type', 'in')->count(),
            'usage' => (clone $logsQuery)->where('type', 'out')->count(),
            'adjustment' => (clone $logsQuery)->where('type', 'adjustment')->count(),
        ];

        $logs = $logsQuery
            ->paginate(10)
            ->withQueryString();

        return view('admin.stocks.logs', compact(
            'logs',
            'summary',
            'period',
            'selectedDate',
            'rangeStart',
            'rangeEnd'
        ));
    }

    public function exportLogs(Request $request)
    {
        $period = $request->input('period', 'daily');
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        try {
            $selectedDate = $request->filled('date')
                ? Carbon::parse($request->input('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable $e) {
            $selectedDate = now()->startOfDay();
        }

        if ($period === 'daily') {
            $rangeStart = $selectedDate->copy()->startOfDay();
            $rangeEnd = $selectedDate->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $rangeStart = $selectedDate->copy()->startOfWeek();
            $rangeEnd = $selectedDate->copy()->endOfWeek();
        } else {
            $rangeStart = $selectedDate->copy()->startOfMonth();
            $rangeEnd = $selectedDate->copy()->endOfMonth();
        }

        $query = StockLog::with('ingredient')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest();

        if ($request->filled('type') && in_array($request->type, ['in', 'out', 'adjustment'], true)) {
            $query->where('type', $request->type);
        }

        $rows = $query->get();

        $filename = sprintf(
            'riwayat-stok-%s-%s_sd_%s.csv',
            $period,
            $rangeStart->toDateString(),
            $rangeEnd->toDateString()
        );

        return response()->streamDownload(function () use ($rows, $period, $rangeStart, $rangeEnd) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Riwayat Stok']);
            fputcsv($output, ['Periode', strtoupper($period)]);
            fputcsv($output, ['Rentang', $rangeStart->toDateString() . ' s/d ' . $rangeEnd->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Tanggal', 'Bahan', 'Tipe', 'Jumlah', 'Sumber', 'Catatan']);

            foreach ($rows as $log) {
                $rawQty = (float) $log->quantity;
                $displayUnit = strtolower(trim((string) ($log->ingredient->display_unit ?? $log->ingredient->base_unit ?? '')));
                $qtyDisplay = in_array($displayUnit, ['kg', 'l'], true) ? $rawQty / 1000 : $rawQty;
                $formattedQty = number_format($qtyDisplay, 2, '.', '');
                $packSuffix = '';

                if ($displayUnit === 'pcs') {
                    $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
                    if ($packSize > 1) {
                        $packValue = $qtyDisplay / $packSize;
                        $packFormatted = rtrim(rtrim(number_format($packValue, 2, '.', ''), '0'), '.');
                        if ($packFormatted === '') {
                            $packFormatted = '0';
                        }
                        $packSuffix = " ({$packFormatted} pack)";
                    }
                }

                if ($log->type === 'in') {
                    $typeLabel = 'Restok';
                    $sourceLabel = 'Manual Restok';
                } elseif ($log->type === 'adjustment') {
                    $typeLabel = 'Penyesuaian';
                    $sourceLabel = 'Manual Adjust';
                } else {
                    $typeLabel = 'Pemakaian';
                    $sourceLabel = $log->reference_id ? 'TRX-' . $log->reference_id : 'Transaksi';
                }

                fputcsv($output, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->ingredient->name ?? '-',
                    $typeLabel,
                    $formattedQty . ' ' . $displayUnit . $packSuffix,
                    $sourceLabel,
                    $log->note ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
