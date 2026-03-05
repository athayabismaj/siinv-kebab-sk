<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\MenuVariant;
use App\Models\StockLog;
use RuntimeException;

class StockService
{

    public static function deductStock(int $variantId, float $qty, int $transactionId, ?string $note = null): void
    {
        $variant = MenuVariant::with('ingredients')->findOrFail($variantId);

        foreach ($variant->ingredients as $ingredient) {
            $usedQty = (float) $ingredient->pivot->quantity * $qty;
            if ($usedQty <= 0) {
                continue;
            }

            // Lock row biar aman dari race condition saat checkout paralel.
            $lockedIngredient = Ingredient::query()
                ->whereKey($ingredient->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $lockedIngredient->stock < $usedQty) {
                throw new RuntimeException("Stok {$lockedIngredient->name} tidak cukup");
            }

            $lockedIngredient->decrement('stock', $usedQty);

            StockLog::create([
                'ingredient_id' => $lockedIngredient->id,
                'type' => 'out',
                // Nilai negatif agar konsisten sebagai stok keluar.
                'quantity' => -$usedQty,
                'reference_id' => $transactionId,
                'note' => $note ?? "Pemakaian bahan dari transaksi #{$transactionId}",
            ]);
        }
    }
}
