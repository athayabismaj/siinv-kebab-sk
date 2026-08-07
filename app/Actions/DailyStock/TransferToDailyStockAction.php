<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockItem;
use App\Models\DailyStockOpeningAdjustment;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use App\Support\IngredientUnit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferToDailyStockAction
{
    /**
     * @param  array<int, array{qty?: float, physical_qty?: ?float, target_opening_qty?: ?float, note: ?string}>  $transfers
     * @return array{
     *     session: DailyStockSession,
     *     processed: int,
     *     returned: int,
     *     reconciled: int,
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
                    'returned' => 0,
                    'reconciled' => 0,
                    'skipped' => [],
                ];
            }

            $processed = 0;
            $returned = 0;
            $reconciled = 0;
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
                $targetOpeningQty = array_key_exists('target_opening_qty', $data)
                    && $data['target_opening_qty'] !== null
                    ? round((float) $data['target_opening_qty'], 2)
                    : null;
                $physicalQty = array_key_exists('physical_qty', $data) && $data['physical_qty'] !== null
                    ? round((float) $data['physical_qty'], 2)
                    : null;

                $ingredient = $ingredients->get($ingredientId);
                if (! $ingredient) {
                    throw new RuntimeException("Bahan dengan ID {$ingredientId} tidak ditemukan.");
                }

                foreach ([$qty, $physicalQty, $targetOpeningQty] as $candidateQuantity) {
                    if ($candidateQuantity !== null
                        && ! IngredientUnit::isValidBaseQuantity(
                            (string) ($ingredient->base_unit ?: $ingredient->display_unit),
                            (float) $candidateQuantity
                        )) {
                        throw new RuntimeException(
                            "Jumlah stok {$ingredient->name} dengan satuan PCS harus berupa bilangan bulat."
                        );
                    }
                }

                $item = DailyStockItem::query()
                    ->where('daily_stock_session_id', $session->id)
                    ->where('ingredient_id', $ingredient->id)
                    ->lockForUpdate()
                    ->first();
                $note = $data['note'] ?? null;

                if ($targetOpeningQty !== null) {
                    $targetResult = $this->applyTargetOpening(
                        $session,
                        $ingredient,
                        $item,
                        $targetOpeningQty,
                        $actorId,
                        $note
                    );

                    $processed += $targetResult['processed'];
                    $returned += $targetResult['returned'];
                    $reconciled += $targetResult['reconciled'];
                    if ($targetResult['skipped'] !== null) {
                        $skipped[] = $targetResult['skipped'];
                    }

                    continue;
                }

                if ($physicalQty !== null && $item && (float) $item->carry_forward_qty > 0) {
                    if ($this->reconcileCarryForward($session, $item, $physicalQty, $actorId, $note)) {
                        $reconciled++;
                    }
                }

                if ($qty <= 0) {
                    continue;
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

                if (! $item) {
                    DailyStockItem::query()->create([
                        'daily_stock_session_id' => $session->id,
                        'ingredient_id' => $ingredient->id,
                        'carry_forward_qty' => 0,
                        'opening_adjustment_qty' => 0,
                        'transferred_qty' => $qty,
                        'opening_qty' => $qty,
                        'remaining_qty' => $qty,
                        'used_qty' => 0,
                        'returned_qty' => 0,
                        'note' => $note,
                    ]);
                } else {
                    $item->opening_qty = (float) $item->opening_qty + $qty;
                    $item->remaining_qty = (float) $item->remaining_qty + $qty;
                    $item->transferred_qty = (float) $item->transferred_qty + $qty;
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
                'returned' => $returned,
                'reconciled' => $reconciled,
                'skipped' => $skipped,
            ];
        });
    }

    /**
     * Menyimpan satu angka total stok awal. Komposisi internal dihitung ulang secara
     * kumulatif agar submit berulang tidak memotong stok gudang dua kali.
     *
     * @return array{
     *     processed: int,
     *     returned: int,
     *     reconciled: int,
     *     skipped: ?array{name: string, requested: float, available: float, unit: string}
     * }
     */
    private function applyTargetOpening(
        DailyStockSession $session,
        Ingredient $ingredient,
        ?DailyStockItem $item,
        float $targetOpeningQty,
        int $actorId,
        ?string $note
    ): array {
        if ($targetOpeningQty < 0) {
            throw new RuntimeException('Stok awal hari ini tidak boleh negatif.');
        }

        $currentOpening = round((float) ($item?->opening_qty ?? 0), 2);
        $currentRemaining = round((float) ($item?->remaining_qty ?? 0), 2);
        $usedQty = round((float) ($item?->used_qty ?? 0), 2);
        $openingDelta = round($targetOpeningQty - $currentOpening, 2);

        if ($openingDelta !== 0.0 && $usedQty > 0) {
            throw new RuntimeException('Stok awal hari ini tidak dapat diubah setelah bahan dipakai dalam transaksi.');
        }

        $carryForward = round((float) ($item?->carry_forward_qty ?? 0), 2);
        $currentTransferred = round((float) ($item?->transferred_qty ?? 0), 2);
        $currentAdjustment = round((float) ($item?->opening_adjustment_qty ?? 0), 2);

        // Nilai di atas carry-forward adalah pengambilan gudang. Nilai di bawahnya
        // adalah koreksi fisik outlet dan tidak pernah mengurangi stok gudang.
        $desiredTransferred = round(max($targetOpeningQty - $carryForward, 0), 2);
        $desiredAdjustment = round(min($targetOpeningQty - $carryForward, 0), 2);
        $transferDelta = round($desiredTransferred - $currentTransferred, 2);
        $adjustmentDelta = round($desiredAdjustment - $currentAdjustment, 2);

        if ($transferDelta > 0 && (float) $ingredient->stock < $transferDelta) {
            return [
                'processed' => 0,
                'returned' => 0,
                'reconciled' => 0,
                'skipped' => [
                    'name' => (string) $ingredient->name,
                    'requested' => $transferDelta,
                    'available' => (float) $ingredient->stock,
                    'unit' => strtolower(trim((string) ($ingredient->base_unit ?: $ingredient->display_unit ?: 'unit'))),
                ],
            ];
        }

        $newRemaining = round($currentRemaining + $openingDelta, 2);
        if ($newRemaining < 0) {
            throw new RuntimeException('Koreksi stok awal menghasilkan saldo negatif.');
        }

        if ($item === null && $targetOpeningQty <= 0) {
            return ['processed' => 0, 'returned' => 0, 'reconciled' => 0, 'skipped' => null];
        }

        if ($transferDelta > 0) {
            $ingredient->decrement('stock', $transferDelta);
        } elseif ($transferDelta < 0) {
            $ingredient->increment('stock', abs($transferDelta));
        }

        $isFirstReconciliation = $item !== null
            && $carryForward > 0
            && $item->carry_forward_reconciled_at === null;

        if ($item === null) {
            $item = DailyStockItem::query()->create([
                'daily_stock_session_id' => $session->id,
                'ingredient_id' => $ingredient->id,
                'carry_forward_qty' => 0,
                'opening_adjustment_qty' => 0,
                'transferred_qty' => $desiredTransferred,
                'opening_qty' => $targetOpeningQty,
                'remaining_qty' => $targetOpeningQty,
                'used_qty' => 0,
                'returned_qty' => 0,
                'note' => $note,
            ]);
        } else {
            if ($adjustmentDelta !== 0.0) {
                DailyStockOpeningAdjustment::query()->create([
                    'daily_stock_session_id' => $session->id,
                    'daily_stock_item_id' => $item->id,
                    'ingredient_id' => $ingredient->id,
                    'expected_qty' => $carryForward,
                    'actual_qty' => round($carryForward + $desiredAdjustment, 2),
                    'difference_qty' => $desiredAdjustment,
                    'created_by' => $actorId,
                    'note' => $note ?: 'Penyesuaian stok awal dari hasil hitung fisik outlet.',
                ]);
            }

            $item->opening_adjustment_qty = $desiredAdjustment;
            $item->transferred_qty = $desiredTransferred;
            $item->opening_qty = $targetOpeningQty;
            $item->remaining_qty = $newRemaining;
            if ($carryForward > 0) {
                $item->carry_forward_reconciled_at = now();
                $item->carry_forward_reconciled_by = $actorId;
            }
            if ($note !== null && trim($note) !== '') {
                $item->note = $note;
            }
            $item->save();
        }

        if ($transferDelta !== 0.0) {
            $isPickup = $transferDelta > 0;
            StockLog::query()->create([
                'branch_id' => $session->branch_id,
                'ingredient_id' => $ingredient->id,
                'type' => $isPickup ? 'transfer_daily' : 'daily_return',
                'quantity' => $isPickup ? -$transferDelta : abs($transferDelta),
                'reference_id' => $session->id,
                'note' => $note ?: ($isPickup
                    ? "Tambahan stok awal sesi #{$session->id} oleh user #{$actorId}"
                    : "Koreksi pengambilan stok awal sesi #{$session->id} oleh user #{$actorId}"),
            ]);
        }

        return [
            'processed' => $transferDelta > 0 ? 1 : 0,
            'returned' => $transferDelta < 0 ? 1 : 0,
            'reconciled' => ($isFirstReconciliation || $adjustmentDelta !== 0.0) ? 1 : 0,
            'skipped' => null,
        ];
    }

    private function reconcileCarryForward(
        DailyStockSession $session,
        DailyStockItem $item,
        float $actualQty,
        int $actorId,
        ?string $note
    ): bool {
        if ($actualQty < 0) {
            throw new RuntimeException('Stok fisik awal tidak boleh negatif.');
        }

        $expected = round((float) $item->carry_forward_qty, 2);
        $desiredAdjustment = round($actualQty - $expected, 2);
        $currentAdjustment = round((float) $item->opening_adjustment_qty, 2);
        $adjustmentDelta = round($desiredAdjustment - $currentAdjustment, 2);
        $isFirstReconciliation = $item->carry_forward_reconciled_at === null;

        if ($adjustmentDelta !== 0.0 && (float) $item->used_qty > 0) {
            throw new RuntimeException(
                'Stok fisik awal tidak dapat dikoreksi setelah bahan dipakai dalam transaksi.'
            );
        }

        $newOpening = round((float) $item->opening_qty + $adjustmentDelta, 2);
        $newRemaining = round((float) $item->remaining_qty + $adjustmentDelta, 2);

        if ($newOpening < 0 || $newRemaining < 0) {
            throw new RuntimeException('Koreksi stok fisik menghasilkan saldo negatif.');
        }

        if ($adjustmentDelta !== 0.0) {
            DailyStockOpeningAdjustment::query()->create([
                'daily_stock_session_id' => $session->id,
                'daily_stock_item_id' => $item->id,
                'ingredient_id' => $item->ingredient_id,
                'expected_qty' => $expected,
                'actual_qty' => $actualQty,
                'difference_qty' => $desiredAdjustment,
                'created_by' => $actorId,
                'note' => $note ?: 'Penyesuaian hasil hitung fisik saldo carry-forward.',
            ]);
        }

        $item->update([
            'opening_adjustment_qty' => $desiredAdjustment,
            'opening_qty' => $newOpening,
            'remaining_qty' => $newRemaining,
            'carry_forward_reconciled_at' => now(),
            'carry_forward_reconciled_by' => $actorId,
        ]);

        return $isFirstReconciliation || $adjustmentDelta !== 0.0;
    }
}
