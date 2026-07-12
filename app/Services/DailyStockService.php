<?php

namespace App\Services;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\User;
use App\Support\AdminCache;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DailyStockService
{
    public function reconcileSessionUsage(int $sessionId): DailyStockSession
    {
        $session = DB::transaction(function () use ($sessionId) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $items = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            $usageByIngredient = $this->inferUsageFromTransactions(
                (int) $session->cashier_id,
                $session->session_date->toDateString(),
                $items->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all()
            );

            foreach ($items as $item) {
                $opening = (float) $item->opening_qty;
                $used = max(
                    (float) $item->used_qty,
                    (float) ($usageByIngredient[(int) $item->ingredient_id] ?? 0)
                );

                if ($used > $opening) {
                    $opening = $used;
                }

                $item->update([
                    'opening_qty' => $opening,
                    'used_qty' => $used,
                    'remaining_qty' => max(0, $opening - $used),
                ]);
            }

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function openSession(
        Carbon|string $sessionDate,
        int $cashierId,
        int $openedBy,
        ?string $notes = null
    ): DailyStockSession {
        $session = DB::transaction(function () use ($sessionDate, $cashierId, $openedBy, $notes) {
            $date = $sessionDate instanceof Carbon
                ? $sessionDate->copy()->startOfDay()->toDateString()
                : Carbon::parse((string) $sessionDate)->startOfDay()->toDateString();
            $branchId = BranchScope::userBranchId(User::query()->with('role')->find($cashierId));

            $session = DailyStockSession::query()
                ->where('session_date', $date)
                ->where('cashier_id', $cashierId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->lockForUpdate()
                ->first();

            if ($session) {
                if ($session->status !== 'open') {
                    throw new RuntimeException('Sesi stok harian untuk tanggal ini sudah ditutup.');
                }

                if ($notes !== null && trim($notes) !== '') {
                    $session->notes = $notes;
                    $session->save();
                }

                return $session;
            }

            return DailyStockSession::query()->create([
                'session_date' => $date,
                'branch_id' => $branchId,
                'cashier_id' => $cashierId,
                'opened_by' => $openedBy,
                'status' => 'open',
                'notes' => $notes,
                'opened_at' => now(),
            ]);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function transferToDaily(
        int $sessionId,
        int $ingredientId,
        float $quantity,
        int $actorId,
        ?string $note = null
    ): DailyStockItem {
        $item = DB::transaction(function () use ($sessionId, $ingredientId, $quantity, $actorId, $note) {
            $qty = $this->normalizeQuantity($quantity);
            if ($qty <= 0) {
                throw new RuntimeException('Jumlah transfer harus lebih dari 0.');
            }

            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'open') {
                throw new RuntimeException('Sesi stok harian sudah ditutup, tidak bisa transfer.');
            }

            $ingredient = Ingredient::query()
                ->whereKey($ingredientId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $ingredient->stock < $qty) {
                throw new RuntimeException("Stok gudang {$ingredient->name} tidak cukup untuk transfer.");
            }

            $ingredient->decrement('stock', $qty);

            $item = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->where('ingredient_id', $ingredient->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                $item = DailyStockItem::query()->create([
                    'daily_stock_session_id' => $session->id,
                    'ingredient_id' => $ingredient->id,
                    'opening_qty' => $qty,
                    'remaining_qty' => $qty,
                    'used_qty' => 0,
                    'returned_qty' => 0,
                    'note' => $note,
                ]);
            } else {
                $item->opening_qty = (float) $item->opening_qty + $qty;
                $item->remaining_qty = (float) $item->remaining_qty + $qty;
                if ($note !== null && trim($note) !== '') {
                    $item->note = $note;
                }
                $item->save();
            }

            StockLog::query()->create([
                'branch_id' => $session->branch_id,
                'ingredient_id' => $ingredient->id,
                'type' => 'transfer_daily',
                'quantity' => -$qty,
                'reference_id' => $session->id,
                'note' => $note ?: "Transfer stok harian sesi #{$session->id} oleh user #{$actorId}",
            ]);

            return $item->fresh();
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $item;
    }

    /**
     * @param array<int, array{qty: float, note: ?string}> $transfers  Key: ingredient_id, Value: array of qty (base unit) and note
     * @return array{
     *     session: DailyStockSession,
     *     processed: int,
     *     skipped: array<int, array{name: string, requested: float, available: float, unit: string}>
     * }
     */
    public function batchTransferToDaily(
        int $sessionId,
        array $transfers,
        int $actorId
    ): array {
        $result = DB::transaction(function () use ($sessionId, $transfers, $actorId) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'open') {
                throw new RuntimeException('Sesi stok harian sudah ditutup, tidak bisa transfer.');
            }

            if (empty($transfers)) {
                return [
                    'session' => $session,
                    'processed' => 0,
                    'skipped' => [],
                ];
            }

            $processed = 0;
            $skipped = [];
            $ingredientIds = array_keys($transfers);
            $ingredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($transfers as $ingredientId => $data) {
                $qty = $this->normalizeQuantity($data['qty'] ?? 0);
                if ($qty <= 0) {
                    continue; // Skip zero transfers
                }

                $ingredient = $ingredients->get($ingredientId);
                if (!$ingredient) {
                    throw new RuntimeException("Bahan dengan ID {$ingredientId} tidak ditemukan.");
                }

                if ((float) $ingredient->stock < $qty) {
                    $skipped[] = [
                        'name' => (string) $ingredient->name,
                        'requested' => $qty,
                        'available' => (float) $ingredient->stock,
                        'unit' => strtolower(trim((string) ($ingredient->base_unit ?: $ingredient->display_unit ?: 'unit'))),
                    ];

                    continue;
                }

                $ingredient->decrement('stock', $qty);

                $item = DailyStockItem::query()
                    ->where('daily_stock_session_id', $session->id)
                    ->where('ingredient_id', $ingredient->id)
                    ->lockForUpdate()
                    ->first();

                $note = $data['note'] ?? null;

                if (! $item) {
                    DailyStockItem::query()->create([
                        'daily_stock_session_id' => $session->id,
                        'ingredient_id' => $ingredient->id,
                        'opening_qty' => $qty,
                        'remaining_qty' => $qty,
                        'used_qty' => 0,
                        'returned_qty' => 0,
                        'note' => $note,
                    ]);
                } else {
                    $item->opening_qty = (float) $item->opening_qty + $qty;
                    $item->remaining_qty = (float) $item->remaining_qty + $qty;
                    if ($note !== null && trim($note) !== '') {
                        $item->note = $note;
                    }
                    $item->save();
                }

                StockLog::query()->create([
                    'branch_id' => $session->branch_id,
                    'ingredient_id' => $ingredient->id,
                    'type' => 'transfer_daily',
                    'quantity' => -$qty,
                    'reference_id' => $session->id,
                    'note' => $note ?: "Transfer batch stok harian sesi #{$session->id} oleh user #{$actorId}",
                ]);

                $processed++;
            }

            return [
                'session' => $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']),
                'processed' => $processed,
                'skipped' => $skipped,
            ];
        });

        if ($result['processed'] > 0) {
            AdminCache::bumpDailyStock();
            AdminCache::bumpDashboard();
            AdminCache::bumpStock();
            AdminCache::bumpCatalog();
        }

        return $result;
    }

    /**
     * @param array<int, float|int|string> $remainingByIngredient
     */
    public function closeSession(
        int $sessionId,
        array $remainingByIngredient,
        int $closedBy,
        ?string $notes = null
    ): DailyStockSession {
        $session = DB::transaction(function () use ($sessionId, $remainingByIngredient, $closedBy, $notes) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'open') {
                throw new RuntimeException('Sesi stok harian sudah ditutup.');
            }

            $items = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            $ingredientIds = $items->pluck('ingredient_id')->unique()->sort()->values()->all();

            $ingredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $opening = (float) $item->opening_qty;
                $usedBefore = (float) $item->used_qty;
                $remainingInput = array_key_exists($item->ingredient_id, $remainingByIngredient)
                    ? (float) $remainingByIngredient[$item->ingredient_id]
                    : (float) $item->remaining_qty;

                $remaining = $this->normalizeQuantity($remainingInput);

                if ($remaining < 0) {
                    throw new RuntimeException('Sisa stok tidak boleh negatif.');
                }

                if ($remaining > $opening) {
                    throw new RuntimeException('Sisa stok tidak boleh lebih besar dari stok bawa.');
                }

                $used = max(0, $opening - $remaining);
                $returned = $remaining;

                $item->remaining_qty = $remaining;
                $item->used_qty = $used;
                $item->returned_qty = $returned;
                $item->save();

                $ingredient = $ingredients->get($item->ingredient_id);
                if (! $ingredient) {
                    throw new RuntimeException("Bahan dengan ID {$item->ingredient_id} tidak ditemukan.");
                }

                $additionalUsage = max(0, $used - $usedBefore);
                if ($additionalUsage > 0) {
                    StockLog::query()->create([
                        'branch_id' => $session->branch_id,
                        'ingredient_id' => $ingredient->id,
                        'type' => 'daily_usage',
                        'quantity' => -$additionalUsage,
                        'reference_id' => $session->id,
                        'note' => "Pemakaian stok harian sesi #{$session->id}",
                    ]);
                }

                if ($returned > 0) {
                    $ingredient->increment('stock', $returned);

                    StockLog::query()->create([
                        'branch_id' => $session->branch_id,
                        'ingredient_id' => $ingredient->id,
                        'type' => 'daily_return',
                        'quantity' => $returned,
                        'reference_id' => $session->id,
                        'note' => "Pengembalian stok harian sesi #{$session->id}",
                    ]);
                }
            }

            $session->update([
                'status' => 'closed',
                'closed_by' => $closedBy,
                'closed_at' => now(),
                'notes' => $notes ?? $session->notes,
            ]);

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function reopenSession(int $sessionId, int $reopenedBy, ?string $notes = null): DailyStockSession
    {
        $session = DB::transaction(function () use ($sessionId, $reopenedBy, $notes) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'closed') {
                throw new RuntimeException('Sesi belum ditutup, tidak perlu reopen.');
            }

            $items = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            $ingredientIds = $items->pluck('ingredient_id')->unique()->sort()->values()->all();

            $ingredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $returned = (float) $item->returned_qty;

                if ($returned > 0) {
                    $ingredient = $ingredients->get($item->ingredient_id);
                    if (! $ingredient) {
                        throw new RuntimeException("Bahan dengan ID {$item->ingredient_id} tidak ditemukan.");
                    }

                    if ((float) $ingredient->stock < $returned) {
                        throw new RuntimeException(
                            "Stok gudang {$ingredient->name} sudah berubah, sesi tidak bisa di-reopen otomatis."
                        );
                    }

                    $ingredient->decrement('stock', $returned);
                }

                $item->update([
                    // Saat reopen, jangan reset pemakaian agar ringkasan terpakai/nilai tetap konsisten.
                    'returned_qty' => 0,
                ]);
            }

            $session = $this->reconcileSessionUsage($session->id);

            StockLog::query()
                ->where('reference_id', $session->id)
                ->where('type', 'daily_return')
                ->delete();

            $session->update([
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
                'notes' => $notes ?: trim(($session->notes ? ($session->notes . ' | ') : '') . "Reopen oleh user #{$reopenedBy}"),
            ]);

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    /**
     * @param array<int, int> $ingredientIds
     * @return array<int, float>
     */
    private function inferUsageFromTransactions(int $cashierId, string $sessionDate, array $ingredientIds): array
    {
        if ($cashierId <= 0 || empty($ingredientIds)) {
            return [];
        }

        $rows = DB::table('stock_logs as sl')
            ->join('transactions as t', 't.id', '=', 'sl.reference_id')
            ->where('sl.type', 'daily_usage')
            ->where('t.user_id', $cashierId)
            ->whereDate('t.created_at', $sessionDate)
            ->whereIn('sl.ingredient_id', $ingredientIds)
            ->groupBy('sl.ingredient_id')
            ->selectRaw('sl.ingredient_id, SUM(ABS(sl.quantity)) as used_total')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->ingredient_id] = (float) $row->used_total;
        }

        return $result;
    }

    private function normalizeQuantity(float $value): float
    {
        return round($value, 2);
    }
}
