<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\StockLog;
use App\Support\IngredientUnit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CloseDailyStockSessionAction
{
    /**
     * @param  array<int, float|int|string>  $remainingByIngredient
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
                ->with('ingredient:id,name,base_unit,display_unit')
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $opening = (float) $item->opening_qty;
                $usedBefore = (float) $item->used_qty;
                $remainingInput = array_key_exists($item->ingredient_id, $remainingByIngredient)
                    ? (float) $remainingByIngredient[$item->ingredient_id]
                    : (float) $item->remaining_qty;
                $remaining = round($remainingInput, 2);

                if ($item->ingredient
                    && ! IngredientUnit::isValidBaseQuantity(
                        (string) ($item->ingredient->base_unit ?: $item->ingredient->display_unit),
                        $remaining
                    )) {
                    throw new RuntimeException(
                        "Sisa stok {$item->ingredient->name} dengan satuan PCS harus berupa bilangan bulat."
                    );
                }

                if ($remaining < 0) {
                    throw new RuntimeException('Sisa stok tidak boleh negatif.');
                }

                if ($remaining > $opening) {
                    throw new RuntimeException('Sisa stok tidak boleh lebih besar dari stok bawa.');
                }

                $used = max(0, $opening - $remaining);
                $item->update([
                    'remaining_qty' => $remaining,
                    'used_qty' => $used,
                    // Sisa fisik tetap berada di outlet dan menjadi carry-forward sesi berikutnya.
                    'returned_qty' => 0,
                ]);

                $additionalUsage = max(0, $used - $usedBefore);
                if ($additionalUsage > 0) {
                    StockLog::query()->create([
                        'branch_id' => $session->branch_id,
                        'ingredient_id' => $item->ingredient_id,
                        'type' => 'daily_usage',
                        'quantity' => -$additionalUsage,
                        'reference_id' => $session->id,
                        'note' => "Pemakaian stok harian sesi #{$session->id}",
                    ]);
                }

            }

            $session->update([
                'status' => 'closed',
                'stock_retained_at_outlet' => true,
                'closed_by' => $closedBy,
                'closed_at' => now(),
                'notes' => $notes ?? $session->notes,
            ]);

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });
    }
}
