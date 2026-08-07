<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STOCK_CONSTRAINT = 'ingredients_whole_pcs_stock_check';

    public function up(): void
    {
        DB::transaction(function (): void {
            // Jangan pernah menciptakan stok saat membersihkan residu: pecahan PCS
            // gudang dibulatkan ke bawah, sedangkan ambang minimum ke bilangan terdekat.
            $pieceIngredients = DB::table('ingredients')
                ->where(function ($query): void {
                    $query->whereRaw("LOWER(TRIM(base_unit)) = 'pcs'")
                        ->orWhereRaw("LOWER(TRIM(display_unit)) = 'pcs'");
                })
                ->get(['id', 'stock', 'minimum_stock']);

            foreach ($pieceIngredients as $ingredient) {
                DB::table('ingredients')
                    ->where('id', $ingredient->id)
                    ->update([
                        'stock' => max(0, floor((float) $ingredient->stock)),
                        'minimum_stock' => max(0, round((float) $ingredient->minimum_stock)),
                    ]);
            }

            if (Schema::hasTable('menu_variant_ingredients')) {
                $pieceIngredientIds = $pieceIngredients->pluck('id');

                if ($pieceIngredientIds->isNotEmpty()) {
                    // Resep PCS lama yang pecahan dibulatkan ke atas agar pemakaian
                    // tidak pernah lebih kecil dari kebutuhan yang pernah dicatat.
                    $fractionalRecipes = DB::table('menu_variant_ingredients')
                        ->whereIn('ingredient_id', $pieceIngredientIds)
                        ->where('quantity', '>', 0)
                        ->get(['menu_variant_id', 'ingredient_id', 'quantity']);

                    foreach ($fractionalRecipes as $recipe) {
                        DB::table('menu_variant_ingredients')
                            ->where('menu_variant_id', $recipe->menu_variant_id)
                            ->where('ingredient_id', $recipe->ingredient_id)
                            ->update(['quantity' => ceil((float) $recipe->quantity)]);
                    }
                }
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                "ALTER TABLE ingredients ADD CONSTRAINT %s CHECK (LOWER(TRIM(base_unit)) <> 'pcs' OR (stock = FLOOR(stock) AND minimum_stock = FLOOR(minimum_stock)))",
                self::STOCK_CONSTRAINT
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE ingredients DROP CONSTRAINT IF EXISTS %s',
                self::STOCK_CONSTRAINT
            ));
        }
    }
};
