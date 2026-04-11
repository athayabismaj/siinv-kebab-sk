<?php

namespace App\Services;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DailyStockService
{
    public function openSession(
        Carbon|string $sessionDate,
        int $cashierId,
        int $openedBy,
        ?string $notes = null
    ): DailyStockSession {
        return DB::transaction(function () use ($sessionDate, $cashierId, $openedBy, $notes) {
            $date = $sessionDate instanceof Carbon
                ? $sessionDate->copy()->startOfDay()->toDateString()
                : Carbon::parse((string) $sessionDate)->startOfDay()->toDateString();

            $session = DailyStockSession::query()
                ->where('session_date', $date)
                ->where('cashier_id', $cashierId)
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
                'cashier_id' => $cashierId,
                'opened_by' => $openedBy,
                'status' => 'open',
                'notes' => $notes,
                'opened_at' => now(),
            ]);
        });
    }

    public function transferToDaily(
        int $sessionId,
        int $ingredientId,
        float $quantity,
        int $actorId,
        ?string $note = null
    ): DailyStockItem {
        return DB::transaction(function () use ($sessionId, $ingredientId, $quantity, $actorId, $note) {
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
                'ingredient_id' => $ingredient->id,
                'type' => 'transfer_daily',
                'quantity' => -$qty,
                'reference_id' => $session->id,
                'note' => $note ?: "Transfer stok harian sesi #{$session->id} oleh user #{$actorId}",
            ]);

            return $item->fresh();
        });
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
        return DB::transaction(function () use ($sessionId, $remainingByIngredient, $closedBy, $notes) {
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

            foreach ($items as $item) {
                $opening = (float) $item->opening_qty;
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

                $ingredient = Ingredient::query()
                    ->whereKey($item->ingredient_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($used > 0) {
                    StockLog::query()->create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'daily_usage',
                        'quantity' => -$used,
                        'reference_id' => $session->id,
                        'note' => "Pemakaian stok harian sesi #{$session->id}",
                    ]);
                }

                if ($returned > 0) {
                    $ingredient->increment('stock', $returned);

                    StockLog::query()->create([
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
    }

    public function reopenSession(int $sessionId, int $reopenedBy, ?string $notes = null): DailyStockSession
    {
        return DB::transaction(function () use ($sessionId, $reopenedBy, $notes) {
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

            foreach ($items as $item) {
                $returned = (float) $item->returned_qty;

                if ($returned > 0) {
                    $ingredient = Ingredient::query()
                        ->whereKey($item->ingredient_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((float) $ingredient->stock < $returned) {
                        throw new RuntimeException(
                            "Stok gudang {$ingredient->name} sudah berubah, sesi tidak bisa di-reopen otomatis."
                        );
                    }

                    $ingredient->decrement('stock', $returned);
                }

                $item->update([
                    'remaining_qty' => (float) $item->opening_qty,
                    'used_qty' => 0,
                    'returned_qty' => 0,
                ]);
            }

            StockLog::query()
                ->where('reference_id', $session->id)
                ->whereIn('type', ['daily_usage', 'daily_return'])
                ->delete();

            $session->update([
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
                'notes' => $notes ?: trim(($session->notes ? ($session->notes . ' | ') : '') . "Reopen oleh user #{$reopenedBy}"),
            ]);

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });
    }

    private function normalizeQuantity(float $value): float
    {
        return round($value, 2);
    }
}
