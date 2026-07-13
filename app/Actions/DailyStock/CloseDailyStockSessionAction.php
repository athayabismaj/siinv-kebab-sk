<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CloseDailyStockSessionAction
{
    /**
     * @param array<int, float|int|string> $remainingByIngredient
     */
    public function execute(
        int $sessionId,
        array $remainingByIngredient,
        int $closedBy,
        ?string $notes = null,
        ?int $branchId = null
    ): DailyStockSession {
        return DB::transaction(function () use ($sessionId, $remainingByIngredient, $closedBy, $notes, $branchId) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
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
                $remaining = round($remainingInput, 2);

                if ($remaining < 0) {
                    throw new RuntimeException('Sisa stok tidak boleh negatif.');
                }

                if ($remaining > $opening) {
                    throw new RuntimeException('Sisa stok tidak boleh lebih besar dari stok bawa.');
                }

                $used = max(0, $opening - $remaining);
                $returned = $remaining;

                $item->update([
                    'remaining_qty' => $remaining,
                    'used_qty' => $used,
                    'returned_qty' => $returned,
                ]);

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
    }
}
