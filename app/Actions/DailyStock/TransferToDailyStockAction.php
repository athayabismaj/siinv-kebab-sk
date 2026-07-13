<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferToDailyStockAction
{
    /**
     * @param array<int, array{qty: float, note: ?string}> $transfers
     * @return array{
     *     session: DailyStockSession,
     *     processed: int,
     *     skipped: array<int, array{name: string, requested: float, available: float, unit: string}>
     * }
     */
    public function executeBatch(int $sessionId, array $transfers, int $actorId, ?int $branchId = null): array
    {
        return DB::transaction(function () use ($sessionId, $transfers, $actorId, $branchId) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
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
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($transfers as $ingredientId => $data) {
                $qty = round((float) ($data['qty'] ?? 0), 2);
                if ($qty <= 0) {
                    continue;
                }

                $ingredient = $ingredients->get($ingredientId);
                if (! $ingredient) {
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
    }
}
