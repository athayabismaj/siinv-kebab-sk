<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\User;
use App\Services\DailyStockService;
use App\Support\IngredientUnit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

        $selectedDate = $this->resolveDate((string) $request->input('date', now()->toDateString()));

        $cashiers = User::query()
            ->with('role:id,name')
            ->whereHas('role', fn ($q) => $q->where('name', 'kasir'))
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        $selectedCashierId = (int) ($request->input('cashier_id') ?: ($cashiers->first()->id ?? 0));

        $session = null;
        if ($selectedCashierId > 0) {
            $session = DailyStockSession::query()
                ->with(['cashier:id,name', 'openedBy:id,name', 'closedBy:id,name', 'items.ingredient:id,name,display_unit,base_unit,pack_size,selling_price'])
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
        }

        $summary = $this->summary($session);

        return view('admin.daily_stocks.index', [
            'selectedDate' => $selectedDate,
            'cashiers' => $cashiers,
            'selectedCashierId' => $selectedCashierId,
            'session' => $session,
            'summary' => $summary,
        ]);
    }

    public function transferForm(Request $request)
    {
        $this->authorize('transfer', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
            'search' => 'nullable|string|max:100',
            'ingredient_id' => 'nullable|exists:ingredients,id',
        ]);

        $session = DailyStockSession::query()
            ->with(['cashier:id,name', 'items.ingredient:id,name,display_unit,pack_size'])
            ->findOrFail((int) $validated['session_id']);

        if (! $this->isOpenStatus($session->status)) {
            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
                ])
                ->with('error', 'Sesi sudah ditutup. Transfer bahan hanya bisa saat sesi aktif.');
        }

        $search = trim((string) ($validated['search'] ?? ''));
        $selectedIngredientId = (int) ($validated['ingredient_id'] ?? 0);

        $ingredients = Ingredient::query()
            ->select(['id', 'name', 'display_unit', 'base_unit', 'pack_size', 'stock'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $this->applyIngredientSearch($query, $search);
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $ingredients->getCollection()->transform(function (Ingredient $ingredient) {
            return $this->decorateTransferIngredient($ingredient);
        });

        $selectedIngredient = $selectedIngredientId > 0
            ? Ingredient::query()->find($selectedIngredientId, ['id', 'name', 'display_unit', 'base_unit', 'pack_size', 'stock'])
            : null;

        if ($selectedIngredient) {
            $this->decorateTransferIngredient($selectedIngredient);
        }

        return view('admin.daily_stocks.transfer', [
            'session' => $session,
            'ingredients' => $ingredients,
            'search' => $search,
            'selectedIngredient' => $selectedIngredient,
        ]);
    }

    public function closeForm(Request $request)
    {
        $this->authorize('close', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
        ]);

        $session = DailyStockSession::query()
            ->with(['cashier:id,name', 'items.ingredient:id,name,display_unit,pack_size'])
            ->findOrFail((int) $validated['session_id']);

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
            'cashier_id' => 'required|exists:users,id',
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
        $this->authorize('transfer', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.01',
            'transfer_unit' => 'nullable|in:pack,pcs,g,kg,ml,l',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $ingredient = Ingredient::query()->findOrFail((int) $validated['ingredient_id']);
            $transferUnit = (string) ($validated['transfer_unit'] ?? 'pack');
            $quantityBase = $this->normalizeQuantityForIngredient(
                $ingredient,
                (float) $validated['quantity'],
                $transferUnit
            );

            $item = $this->dailyStockService->transferToDaily(
                (int) $validated['session_id'],
                (int) $validated['ingredient_id'],
                $quantityBase,
                (int) auth()->id(),
                $validated['note'] ?? null
            );

            $session = DailyStockSession::query()->findOrFail((int) $validated['session_id']);

            return redirect()
                ->route('admin.daily-stocks.transfer.form', [
                    'session_id' => $session->id,
                    'ingredient_id' => $item->ingredient_id,
                ])
                ->with('success', "Transfer stok harian untuk {$item->ingredient->name} berhasil.");
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable) {
            return back()->withInput()->with('error', 'Gagal transfer stok ke sesi harian.');
        }
    }

    public function close(Request $request)
    {
        $this->authorize('close', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
            'remaining' => 'nullable|array',
            'remaining.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $session = DailyStockSession::query()
                ->with('items.ingredient')
                ->findOrFail((int) $validated['session_id']);

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
        $this->authorize('reopen', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
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
        $this->authorize('reopen', DailyStockSession::class);

        $validated = $request->validate([
            'session_id' => 'required|exists:daily_stock_sessions,id',
        ]);

        try {
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
                'total_opening' => 0,
                'total_remaining' => 0,
                'total_used' => 0,
            ];
        }

        return [
            'items_count'     => $session->items->count(),
            'total_opening'   => (float) $session->items->sum('opening_qty'),
            'total_remaining' => (float) $session->items->sum('remaining_qty'),
            'total_used'      => (float) $session->items->sum('used_qty'),
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
