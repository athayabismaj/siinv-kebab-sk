<?php

namespace App\Actions\Inventory;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Support\IngredientUnit;
use Illuminate\Support\Facades\DB;

class AdjustInventoryStockAction
{
    public function execute(
        int $ingredientId,
        float $newStock,
        ?string $inputUnit,
        string $note,
        ?int $branchId
    ): ?Ingredient {
        return DB::transaction(function () use ($ingredientId, $newStock, $inputUnit, $note, $branchId) {
            $ingredient = Ingredient::query()
                ->whereKey($ingredientId)
                ->lockForUpdate()
                ->firstOrFail();
            $newStockInBaseUnit = $this->normalizeQuantity($ingredient, $newStock, $inputUnit);
            $currentStock = (float) $ingredient->stock;

            if (round($newStockInBaseUnit, 2) === round($currentStock, 2)) {
                return null;
            }

            $ingredient->update(['stock' => $newStockInBaseUnit]);

            StockLog::query()->create([
                'branch_id' => $branchId,
                'ingredient_id' => $ingredient->id,
                'type' => 'adjustment',
                'quantity' => $newStockInBaseUnit - $currentStock,
                'note' => $note,
            ]);

            return $ingredient->fresh();
        });
    }

    private function normalizeQuantity(Ingredient $ingredient, float $value, ?string $inputUnit): float
    {
        if ((string) $ingredient->display_unit === 'pcs') {
            return $inputUnit === 'pcs'
                ? $value
                : $value * max(1, (int) ($ingredient->pack_size ?? 1));
        }

        return IngredientUnit::toBase((string) $ingredient->display_unit, $value);
    }
}
