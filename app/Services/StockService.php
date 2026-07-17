<?php

namespace App\Services;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\MenuVariant;
use App\Models\StockLog;
use App\Support\AdminCache;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class StockService
{

    public static function deductStock(
        int $variantId,
        float $qty,
        int $transactionId,
        ?string $note = null,
        ?int $cashierId = null,
        Carbon|string|null $transactionAt = null,
        ?int $branchId = null
    ): void
    {
        $cashierId = (int) ($cashierId ?? 0);
        if ($cashierId <= 0) {
            throw new RuntimeException('Kasir tidak valid untuk pengurangan stok harian.');
        }

        $sessionDate = $transactionAt instanceof Carbon
            ? $transactionAt->copy()->setTimezone('Asia/Jakarta')->startOfDay()->toDateString()
            : Carbon::parse((string) ($transactionAt ?? now('Asia/Jakarta')), 'Asia/Jakarta')
                ->setTimezone('Asia/Jakarta')
                ->startOfDay()
                ->toDateString();

        $session = DailyStockSession::query()
            ->where('cashier_id', $cashierId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereDate('session_date', $sessionDate)
            ->whereRaw("LOWER(TRIM(status)) = 'open'")
            ->lockForUpdate()
            ->first();

        if (! $session) {
            throw new RuntimeException(
                'Sesi stok harian kasir belum dibuka. Buka sesi dan transfer bahan terlebih dahulu.'
            );
        }

        $variant = MenuVariant::with('ingredients')->findOrFail($variantId);

        foreach ($variant->ingredients as $ingredient) {
            $usedQty = (float) $ingredient->pivot->quantity * $qty;
            if ($usedQty <= 0) {
                continue;
            }

            // Lock row ingredient hanya untuk memastikan data ingredient konsisten saat validasi relasi recipe.
            try {
                $lockedIngredient = Ingredient::query()
                    ->whereKey($ingredient->id)
                    ->lock('FOR UPDATE NOWAIT')
                    ->firstOrFail();
            } catch (QueryException $e) {
                // PostgreSQL: 55P03 = lock_not_available
                if (($e->errorInfo[0] ?? null) === '55P03') {
                    throw new RuntimeException(
                        "Stok {$ingredient->name} sedang diproses kasir lain. Coba lagi beberapa detik."
                    );
                }

                throw $e;
            }

            $dailyItem = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->where('ingredient_id', $lockedIngredient->id)
                ->lockForUpdate()
                ->first();

            if (! $dailyItem) {
                throw new RuntimeException(
                    "Bahan {$lockedIngredient->name} belum dibawa ke stok harian kasir."
                );
            }

            if ((float) $dailyItem->remaining_qty < $usedQty) {
                throw new RuntimeException(
                    "Stok harian {$lockedIngredient->name} tidak cukup."
                );
            }

            $dailyItem->decrement('remaining_qty', $usedQty);
            $dailyItem->increment('used_qty', $usedQty);

            StockLog::create([
                'branch_id' => $branchId ?: $session->branch_id,
                'ingredient_id' => $lockedIngredient->id,
                'type' => 'daily_usage',
                'quantity' => -$usedQty,
                'reference_id' => $transactionId,
                'note' => $note ?? "Pemakaian stok harian dari transaksi #{$transactionId}",
            ]);
        }

        AdminCache::bumpStock();
        AdminCache::bumpUsage();
        AdminCache::bumpDailyStock();
        AdminCache::bumpCatalog();
    }
}
