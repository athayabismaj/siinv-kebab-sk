<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\User;
use App\Services\DailyStockService;
use App\Support\IngredientUnit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DailyStockController extends Controller
{
    public function __construct(
        private readonly DailyStockService $dailyStockService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyStockSession::class);

        if ((string) $request->input('category_id') === '0') {
            $request->merge(['category_id' => null]);
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'category_id' => [
                'nullable',
                Rule::exists((new IngredientCategory())->getTable(), 'id'),
            ],
        ]);

        $selectedDate = $this->resolveDate((string) $request->input('date', now()->toDateString()));
        $search = trim((string) ($validated['search'] ?? ''));
        $selectedCategoryId = (int) ($validated['category_id'] ?? 0);

        $cashiers = User::query()
            ->with('role:id,name')
            ->whereHas('role', fn ($q) => $q->where('name', 'kasir'))
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        $selectedCashierId = (int) ($request->input('cashier_id') ?: ($cashiers->first()->id ?? 0));

        $session = null;
        if ($selectedCashierId > 0) {
            $session = DailyStockSession::query()
                ->with(['cashier:id,name', 'openedBy:id,name', 'closedBy:id,name', 'items.ingredient:id,category_id,name,display_unit,base_unit,pack_size,selling_price'])
                ->where('session_date', $selectedDate->toDateString())
                ->where('cashier_id', $selectedCashierId)
                ->first();

            if ($session && $this->isOpenStatus($session->status)) {
                try {
                    $session = $this->dailyStockService->reconcileSessionUsage($session->id);
                } catch (\Throwable $e) {
                    Log::warning('Failed to reconcile daily stock session usage', [
                        'session_id' => $session->id,
                        'cashier_id' => $selectedCashierId,
                        'date' => $selectedDate->toDateString(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($session) {
            $sessionCategoryIds = $session->items
                ->pluck('ingredient.category_id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $session->setRelation('items', $session->items->map(function ($item) {
                $ingredient = $item->ingredient;

                $item->opening_display = $this->toDisplayQuantity($ingredient, (float) $item->opening_qty);
                $item->remaining_display = $this->toDisplayQuantity($ingredient, (float) $item->remaining_qty);
                $item->used_display = $this->toDisplayQuantity($ingredient, (float) $item->used_qty);
                $item->display_unit = strtolower((string) ($ingredient->display_unit ?? ''));
                $item->pack_size = max(1, (int) ($ingredient->pack_size ?? 1));
                $item->opening_pack = $item->display_unit === 'pcs' && $item->pack_size > 1
                    ? round($item->opening_display / $item->pack_size, 2)
                    : null;
                $item->remaining_pack = $item->display_unit === 'pcs' && $item->pack_size > 1
                    ? round($item->remaining_display / $item->pack_size, 2)
                    : null;

                return $item;
            }));

            $filteredItems = $session->items;

            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $filteredItems = $filteredItems->filter(function ($item) use ($searchLower) {
                    $name = mb_strtolower((string) optional($item->ingredient)->name);
                    return str_contains($name, $searchLower);
                })->values();
            }

            if ($selectedCategoryId > 0) {
                $filteredItems = $filteredItems->filter(function ($item) use ($selectedCategoryId) {
                    return (int) optional($item->ingredient)->category_id === $selectedCategoryId;
                })->values();
            }

            $session->setRelation('items', $filteredItems);
        }

        $summary = $this->summary($session);

        $sessionCategoryIds = $sessionCategoryIds ?? [];

        $categories = empty($sessionCategoryIds)
            ? collect()
            : IngredientCategory::query()
                ->whereIn('id', $sessionCategoryIds)
                ->orderBy('name')
                ->get(['id', 'name']);

        return view('admin.daily_stocks.index', [
            'selectedDate' => $selectedDate,
            'cashiers' => $cashiers,
            'selectedCashierId' => $selectedCashierId,
            'session' => $session,
            'summary' => $summary,
            'categories' => $categories,
            'search' => $search,
            'selectedCategoryId' => $selectedCategoryId,
        ]);
    }

    public function transferForm(Request $request)
    {
        if ((string) $request->input('category_id') === '0') {
            $request->merge(['category_id' => null]);
        }

        $validated = $request->validate([
            'session_id' => 'required|integer|min:1',
            'search' => 'nullable|string|max:100',
            'category_id' => [
                'nullable',
                Rule::exists((new IngredientCategory())->getTable(), 'id'),
            ],
            'ingredient_id' => 'nullable|integer|min:1',
        ]);

        $session = DailyStockSession::query()
            ->with(['cashier:id,name', 'items.ingredient:id,name,display_unit,pack_size'])
            ->findOrFail((int) $validated['session_id']);

        $this->authorize('transfer', $session);

        if (! $this->isOpenStatus($session->status)) {
            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('error', 'Sesi sudah ditutup. Transfer bahan hanya bisa saat sesi aktif.');
        }

        $search = trim((string) ($validated['search'] ?? ''));
        $selectedCategoryId = (int) ($validated['category_id'] ?? 0);
        $selectedIngredientId = (int) ($validated['ingredient_id'] ?? 0);

        $ingredients = Ingredient::query()
            ->select(['id', 'name', 'display_unit', 'base_unit', 'pack_size', 'stock'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $this->applyIngredientSearch($query, $search);
            })
            ->when($selectedCategoryId > 0, function (Builder $query) use ($selectedCategoryId) {
                $query->where('category_id', $selectedCategoryId);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $ingredients->getCollection()->transform(function (Ingredient $ingredient) {
            return $this->decorateTransferIngredient($ingredient);
        });

        $selectedIngredient = null;
        if ($selectedIngredientId > 0) {
            $selectedIngredient = Ingredient::query()
                ->where('id', $selectedIngredientId)
                ->whereNull('deleted_at')
                ->first(['id', 'name', 'display_unit', 'base_unit', 'pack_size', 'stock']);
        }

        if ($selectedIngredient) {
            $this->decorateTransferIngredient($selectedIngredient);
        }

        $categories = IngredientCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.daily_stocks.transfer', [
            'session' => $session,
            'ingredients' => $ingredients,
            'search' => $search,
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedIngredient' => $selectedIngredient,
        ]);
    }

    public function closeForm(Request $request)
    {
        $validated = $request->validate([
            'session_id' => [
                'required',
                Rule::exists((new DailyStockSession())->getTable(), 'id'),
            ],
        ]);

        $session = DailyStockSession::query()
            ->with(['cashier:id,name', 'items.ingredient:id,name,display_unit,pack_size'])
            ->findOrFail((int) $validated['session_id']);

        $this->authorize('close', $session);

        if (! $this->isOpenStatus($session->status)) {
            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('error', 'Sesi sudah ditutup.');
        }

        $session->setRelation('items', $session->items->map(function ($item) {
            $ingredient = $item->ingredient;
            $item->opening_display = $this->toDisplayQuantity($ingredient, (float) $item->opening_qty);
            $item->remaining_display = $this->toDisplayQuantity($ingredient, (float) $item->remaining_qty);
            $item->display_unit = strtolower((string) ($ingredient->display_unit ?? ''));
            $item->pack_size = max(1, (int) ($ingredient->pack_size ?? 1));

            return $item;
        }));

        return view('admin.daily_stocks.close', [
            'session' => $session,
            'summary' => $this->summary($session),
        ]);
    }

    public function open(Request $request)
    {
        $this->authorize('open', DailyStockSession::class);

        $validated = $request->validate([
            'date' => 'required|date',
            'cashier_id' => [
                'required',
                Rule::exists((new User())->getTable(), 'id'),
            ],
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $session = $this->dailyStockService->openSession(
                $validated['date'],
                (int) $validated['cashier_id'],
                (int) auth()->id(),
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('success', 'Sesi stok harian berhasil dibuka.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal membuka sesi stok harian.');
        }
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'session_id' => [
                'required',
                Rule::exists((new DailyStockSession())->getTable(), 'id'),
            ],
            'transfers' => 'nullable|array',
            'transfers.*.quantity' => 'nullable|numeric|min:0',
            'transfers.*.note' => 'nullable|string|max:255',
            'transfers.*.transfer_unit' => 'nullable|in:pack,pcs,g,kg,ml,l',
        ], [
            'session_id.required' => 'Sesi stok harian belum dipilih.',
            'session_id.exists' => 'Sesi stok harian tidak ditemukan atau sudah tidak aktif.',
            'transfers.array' => 'Data transfer tidak valid.',
        ]);

        try {
            $session = DailyStockSession::query()->findOrFail((int) $validated['session_id']);
            $this->authorize('transfer', $session);

            $rawTransfers = $validated['transfers'] ?? [];
            $batchTransfers = [];
            
            if (!empty($rawTransfers)) {
                $ingredientIds = array_keys($rawTransfers);
                $ingredients = Ingredient::query()->whereIn('id', $ingredientIds)->get()->keyBy('id');
                
                foreach ($rawTransfers as $ingredientId => $data) {
                    $qty = (float) ($data['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    
                    $ingredient = $ingredients->get($ingredientId);
                    if (!$ingredient) {
                        continue;
                    }
                    
                    $transferUnit = (string) ($data['transfer_unit'] ?? 'pack');
                    $quantityBase = $this->normalizeQuantityForIngredient(
                        $ingredient,
                        $qty,
                        $transferUnit
                    );
                    
                    $batchTransfers[$ingredient->id] = [
                        'qty' => $quantityBase,
                        'note' => $data['note'] ?? null,
                    ];
                }
            }

            if (!empty($batchTransfers)) {
                $this->dailyStockService->batchTransferToDaily(
                    (int) $validated['session_id'],
                    $batchTransfers,
                    (int) auth()->id()
                );
                
                return redirect()
                    ->route('admin.daily-stocks.transfer.form', [
                        'session_id' => $session->id,
                        'search' => $request->query('search'),
                        'category_id' => $request->query('category_id'),
                        'page' => $request->query('page'),
                    ])
                    ->with('success', "Transfer batch stok harian berhasil disimpan.");
            }

            return back()->with('success', "Tidak ada bahan yang ditransfer (jumlah 0).");
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal transfer stok ke sesi harian.');
        }
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'session_id' => [
                'required',
                Rule::exists((new DailyStockSession())->getTable(), 'id'),
            ],
            'remaining' => 'nullable|array',
            'remaining.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $session = DailyStockSession::query()
                ->with('items.ingredient')
                ->findOrFail((int) $validated['session_id']);

            $this->authorize('close', $session);

            $remainingRaw = $validated['remaining'] ?? [];
            $remainingByIngredient = [];

            foreach ($session->items as $item) {
                $rawDisplay = (float) ($remainingRaw[$item->ingredient_id] ?? $this->toDisplayQuantity($item->ingredient, (float) $item->remaining_qty));
                $remainingByIngredient[$item->ingredient_id] = $this->normalizeQuantityForIngredient($item->ingredient, $rawDisplay, 'pcs');
            }

            $closed = $this->dailyStockService->closeSession(
                $session->id,
                $remainingByIngredient,
                (int) auth()->id(),
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $closed->session_date->toDateString(),
                    'cashier_id' => $closed->cashier_id,
                ])
                ->with('success', 'Sesi stok harian berhasil ditutup.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal menutup sesi stok harian.');
        }
    }

    public function reopen(Request $request)
    {
        $validated = $request->validate([
            'session_id' => [
                'required',
                Rule::exists((new DailyStockSession())->getTable(), 'id'),
            ],
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $sessionModel = DailyStockSession::query()->findOrFail((int) $validated['session_id']);
            $this->authorize('reopen', $sessionModel);

            $session = $this->dailyStockService->reopenSession(
                (int) $validated['session_id'],
                (int) auth()->id(),
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('success', 'Sesi stok harian berhasil di-reopen.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal reopen sesi stok harian.');
        }
    }

    public function reconcile(Request $request)
    {
        $validated = $request->validate([
            'session_id' => [
                'required',
                Rule::exists((new DailyStockSession())->getTable(), 'id'),
            ],
        ]);

        try {
            $sessionModel = DailyStockSession::query()->findOrFail((int) $validated['session_id']);
            $this->authorize('reopen', $sessionModel);

            $session = $this->dailyStockService->reconcileSessionUsage((int) $validated['session_id']);

            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('success', 'Rekonsiliasi data sesi berhasil.');
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal melakukan rekonsiliasi data sesi.');
        }
    }

    private function resolveDate(string $date): Carbon
    {
        try {
            $parsed = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            $parsed = now()->startOfDay();
        }

        return $parsed;
    }

    private function summary(?DailyStockSession $session): array
    {
        if (! $session) {
            return [
                'items_count' => 0,
                'by_unit'     => [],
                'total_value' => 0,
            ];
        }

        // Kelompokkan per-satuan agar tidak menjumlahkan PCS + Liter + kg (tidak bermakna)
        $grouped = $session->items->groupBy(function ($item) {
            return strtoupper(trim((string) ($item->ingredient->base_unit ?? $item->ingredient->display_unit ?? 'Unit')));
        });

        $byUnit = [];
        foreach ($grouped as $unit => $unitItems) {
            $byUnit[] = [
                'unit'      => $unit,
                'count'     => $unitItems->count(),
                'opening'   => round((float) $unitItems->sum('opening_qty'), 2),
                'remaining' => round((float) $unitItems->sum('remaining_qty'), 2),
                'used'      => round((float) $unitItems->sum('used_qty'), 2),
            ];
        }

        return [
            'items_count'     => $session->items->count(),
            'by_unit'         => $byUnit,
            'total_value'     => (float) $session->items->sum(function ($item) {
                $usedQty  = (float) $item->used_qty;
                $selPrice = (float) ($item->ingredient->selling_price ?? 0);
                $dispUnit = strtolower((string) ($item->ingredient->display_unit ?? ''));
                $packSize = max(1, (int) ($item->ingredient->pack_size ?? 1));

                return match($dispUnit) {
                    'kg', 'l' => ($usedQty / 1000) * $selPrice,
                    'pcs'     => ($usedQty / $packSize) * $selPrice,
                    default   => $usedQty * $selPrice,
                };
            }),
        ];
    }

    private function normalizeQuantityForIngredient(Ingredient $ingredient, float $value, string $transferUnit = 'pack'): float
    {
        $displayUnit = strtolower((string) $ingredient->display_unit);
        $transferUnit = strtolower(trim($transferUnit));

        if ($displayUnit === 'pcs') {
            if ($transferUnit === 'pcs') {
                return round($value, 2);
            }

            return round($value * max(1, (int) ($ingredient->pack_size ?? 1)), 2);
        }

        if (in_array($transferUnit, ['g', 'kg', 'ml', 'l'], true)) {
            return round(IngredientUnit::toBase($transferUnit, $value), 2);
        }

        return round(IngredientUnit::toBase($displayUnit, $value), 2);
    }

    private function toDisplayQuantity(Ingredient $ingredient, float $baseValue): float
    {
        $unit = strtolower((string) $ingredient->display_unit);
        if (in_array($unit, ['kg', 'l'], true)) {
            return round($baseValue / 1000, 2);
        }

        return round($baseValue, 2);
    }

    private function isOpenStatus(?string $status): bool
    {
        return strtolower(trim((string) $status)) === 'open';
    }

    private function applyIngredientSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $driver = config('database.connections.' . config('database.default') . '.driver');
        $term = '%' . $search . '%';

        if ($driver === 'pgsql') {
            $query->where('name', 'ILIKE', $term);

            return;
        }

        $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
    }

    private function decorateTransferIngredient(Ingredient $ingredient): Ingredient
    {
        $stock = (float) $ingredient->stock;
        $displayUnit = strtolower(trim((string) $ingredient->display_unit));
        $baseUnit = strtolower(trim((string) ($ingredient->base_unit ?: IngredientUnit::baseUnit($displayUnit))));

        $ingredient->transfer_stock_value = $stock;
        $ingredient->transfer_stock_unit = $displayUnit;
        $ingredient->transfer_input_unit = $displayUnit;
        $ingredient->transfer_unit_options = [$displayUnit => $displayUnit];

        if ($displayUnit === 'kg') {
            $ingredient->transfer_stock_value = round($stock / 1000, 2);
            $ingredient->transfer_stock_unit = 'kg';
            $ingredient->transfer_input_unit = 'kg';
            $ingredient->transfer_unit_options = ['kg' => 'Kilogram (kg)', 'g' => 'Gram (g)'];
        } elseif ($displayUnit === 'l') {
            $ingredient->transfer_stock_value = round($stock / 1000, 2);
            $ingredient->transfer_stock_unit = 'l';
            $ingredient->transfer_input_unit = 'l';
            $ingredient->transfer_unit_options = ['l' => 'Liter (l)', 'ml' => 'Mililiter (ml)'];
        } elseif (in_array($baseUnit, ['g', 'ml', 'pcs'], true)) {
            $ingredient->transfer_stock_unit = $baseUnit;
            $ingredient->transfer_input_unit = $baseUnit;
        }

        return $ingredient;
    }
}
