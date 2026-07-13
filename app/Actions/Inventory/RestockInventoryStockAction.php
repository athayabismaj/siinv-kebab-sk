<?php

namespace App\Actions\Inventory;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Support\IngredientUnit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestockInventoryStockAction
{
    public function execute(
        int $ingredientId,
        float $quantity,
        ?string $inputUnit,
        ?string $note,
        ?int $branchId
    ): Ingredient {
        return DB::transaction(function () use ($ingredientId, $quantity, $inputUnit, $note, $branchId) {
            $ingredient = Ingredient::query()
                ->whereKey($ingredientId)
                ->lockForUpdate()
                ->firstOrFail();
            $quantityInBaseUnit = $this->normalizeQuantity($ingredient, $quantity, $inputUnit);

            if ($quantityInBaseUnit <= 0) {
                throw new RuntimeException('Jumlah restok harus lebih dari 0.');
            }

            $ingredient->increment('stock', $quantityInBaseUnit);

            StockLog::query()->create([
                'branch_id' => $branchId,
                'ingredient_id' => $ingredient->id,
                'type' => 'in',
                'quantity' => $quantityInBaseUnit,
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
