<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\User;
use App\Services\DailyStockService;
use App\Support\IngredientUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
                ->with(['cashier:id,name', 'openedBy:id,name', 'closedBy:id,name', 'items.ingredient:id,name,display_unit,pack_size'])
                ->where('session_date', $selectedDate->toDateString())
                ->where('cashier_id', $selectedCashierId)
                ->first();
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

        $ingredients = Ingredient::query()
            ->orderBy('name')
            ->get(['id', 'name', 'display_unit', 'pack_size', 'stock']);

        $summary = $this->summary($session);

        return view('admin.daily_stocks.index', [
            'selectedDate' => $selectedDate,
            'cashiers' => $cashiers,
            'selectedCashierId' => $selectedCashierId,
            'session' => $session,
            'ingredients' => $ingredients,
            'summary' => $summary,
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
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $ingredient = Ingredient::query()->findOrFail((int) $validated['ingredient_id']);
            $quantityBase = $this->normalizeQuantityForIngredient($ingredient, (float) $validated['quantity']);

            $item = $this->dailyStockService->transferToDaily(
                (int) $validated['session_id'],
                (int) $validated['ingredient_id'],
                $quantityBase,
                (int) auth()->id(),
                $validated['note'] ?? null
            );

            $session = DailyStockSession::query()->findOrFail((int) $validated['session_id']);

            return redirect()
                ->route('admin.daily-stocks.index', [
                    'date' => $session->session_date->toDateString(),
                    'cashier_id' => $session->cashier_id,
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
                $remainingByIngredient[$item->ingredient_id] = $this->normalizeQuantityForIngredient($item->ingredient, $rawDisplay);
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
            'items_count' => $session->items->count(),
            'total_opening' => (float) $session->items->sum('opening_qty'),
            'total_remaining' => (float) $session->items->sum('remaining_qty'),
            'total_used' => (float) $session->items->sum('used_qty'),
        ];
    }

    private function normalizeQuantityForIngredient(Ingredient $ingredient, float $value): float
    {
        if ((string) $ingredient->display_unit === 'pcs') {
            return round($value * max(1, (int) ($ingredient->pack_size ?? 1)), 2);
        }

        return round(IngredientUnit::toBase((string) $ingredient->display_unit, $value), 2);
    }

    private function toDisplayQuantity(Ingredient $ingredient, float $baseValue): float
    {
        $unit = strtolower((string) $ingredient->display_unit);
        if (in_array($unit, ['kg', 'l'], true)) {
            return round($baseValue / 1000, 2);
        }

        return round($baseValue, 2);
    }
}
