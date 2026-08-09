<?php

namespace App\Services;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\MenuVariant;
use App\Models\StockLog;
use App\Support\AdminCache;
use App\Support\IngredientUnit;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Kurangi seluruh bahan dalam satu checkout dengan jumlah query tetap.
     *
     * @param  array<int, array{ingredient_id:int,name:string,base_unit:string,quantity:float}>  $usages
     */
    public static function deductDailyStockBatch(
        int $sessionId,
        array $usages,
        int $transactionId,
        ?string $note = null,
        ?int $cashierId = null,
        ?int $branchId = null,
        ?DailyStockSession $lockedSession = null,
    ): void {
        $cashierId = (int) ($cashierId ?? 0);
        if ($cashierId <= 0) {
            throw new RuntimeException('Kasir tidak valid untuk pengurangan stok harian.');
        }

        $usageByIngredient = [];
        foreach ($usages as $usage) {
            $ingredientId = (int) ($usage['ingredient_id'] ?? 0);
            $quantity = (float) ($usage['quantity'] ?? 0);
            if ($ingredientId <= 0 || $quantity <= 0) {
                continue;
            }

            if (isset($usageByIngredient[$ingredientId])) {
                $usageByIngredient[$ingredientId]['quantity'] += $quantity;

                continue;
            }

            $usageByIngredient[$ingredientId] = [
                'ingredient_id' => $ingredientId,
                'name' => (string) ($usage['name'] ?? 'Bahan'),
                'base_unit' => (string) ($usage['base_unit'] ?? ''),
                'quantity' => $quantity,
            ];
        }

        if ($usageByIngredient === []) {
            throw new RuntimeException('Resep transaksi tidak memiliki pemakaian bahan yang valid.');
        }

        ksort($usageByIngredient);
        $ingredientIds = array_keys($usageByIngredient);

        $session = $lockedSession;
        if (! $session) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->where('cashier_id', $cashierId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereRaw("LOWER(TRIM(status)) = 'open'")
                ->lockForUpdate()
                ->first();
        }

        if (
            ! $session
            || (int) $session->id !== $sessionId
            || (int) $session->cashier_id !== $cashierId
            || ($branchId && (int) $session->branch_id !== $branchId)
            || strtolower(trim((string) $session->status)) !== 'open'
        ) {
            throw new RuntimeException(
                'Sesi stok harian kasir belum dibuka. Buka sesi dan transfer bahan terlebih dahulu.'
            );
        }

        $dailyItems = $session->relationLoaded('items')
            ? $session->items->whereIn('ingredient_id', $ingredientIds)->keyBy('ingredient_id')
            : DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->whereIn('ingredient_id', $ingredientIds)
                ->orderBy('ingredient_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('ingredient_id');

        foreach ($usageByIngredient as $ingredientId => $usage) {
            $usedQty = (float) $usage['quantity'];

            if (! IngredientUnit::isValidBaseQuantity(
                (string) $usage['base_unit'],
                $usedQty,
            )) {
                throw new RuntimeException(
                    "Resep transaksi menghasilkan pecahan PCS untuk {$usage['name']}. Perbaiki resep terlebih dahulu."
                );
            }

            $dailyItem = $dailyItems->get($ingredientId);
            if (! $dailyItem) {
                throw new RuntimeException("Bahan {$usage['name']} belum dibawa ke stok harian kasir.");
            }

            if ((float) $dailyItem->remaining_qty < $usedQty) {
                throw new RuntimeException("Stok harian {$usage['name']} tidak cukup.");
            }
        }

        self::updateDailyItemsInOneQuery($session->id, $usageByIngredient);

        $timestamp = now();
        DB::table('stock_logs')->insert(array_values(array_map(
            fn (array $usage): array => [
                'branch_id' => $branchId ?: $session->branch_id,
                'ingredient_id' => $usage['ingredient_id'],
                'type' => 'daily_usage',
                'quantity' => -((float) $usage['quantity']),
                'reference_id' => $transactionId,
                'note' => $note ?? "Pemakaian stok harian dari transaksi #{$transactionId}",
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $usageByIngredient,
        )));

        AdminCache::bumpStock();
        AdminCache::bumpUsage();
        AdminCache::bumpDailyStock();
        AdminCache::bumpCatalog();
    }

    public static function deductStock(
        int $variantId,
        float $qty,
        int $transactionId,
        ?string $note = null,
        ?int $cashierId = null,
        Carbon|string|null $transactionAt = null,
        ?int $branchId = null
    ): void {
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

            if (! IngredientUnit::isValidBaseQuantity(
                (string) ($ingredient->base_unit ?: $ingredient->display_unit),
                $usedQty
            )) {
                throw new RuntimeException(
                    "Resep {$variant->name} menghasilkan pecahan PCS untuk {$ingredient->name}. Perbaiki resep terlebih dahulu."
                );
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

    /**
     * @param  array<int, array{ingredient_id:int,name:string,base_unit:string,quantity:float}>  $usageByIngredient
     */
    private static function updateDailyItemsInOneQuery(int $sessionId, array $usageByIngredient): void
    {
        $caseSql = [];
        $caseBindings = [];
        $ingredientIds = [];

        foreach ($usageByIngredient as $usage) {
            $caseSql[] = 'WHEN ? THEN ?';
            $caseBindings[] = (int) $usage['ingredient_id'];
            $caseBindings[] = (float) $usage['quantity'];
            $ingredientIds[] = (int) $usage['ingredient_id'];
        }

        $cases = implode(' ', $caseSql);
        $idPlaceholders = implode(', ', array_fill(0, count($ingredientIds), '?'));
        $sql = "UPDATE daily_stock_items
            SET remaining_qty = remaining_qty - CASE ingredient_id {$cases} ELSE 0 END,
                used_qty = used_qty + CASE ingredient_id {$cases} ELSE 0 END,
                updated_at = ?
            WHERE daily_stock_session_id = ?
              AND ingredient_id IN ({$idPlaceholders})";

        DB::update($sql, [
            ...$caseBindings,
            ...$caseBindings,
            now(),
            $sessionId,
            ...$ingredientIds,
        ]);
    }
}
