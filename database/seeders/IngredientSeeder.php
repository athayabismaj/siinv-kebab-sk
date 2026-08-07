<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IngredientCategory;
use App\Models\Ingredient;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data lama
        Ingredient::query()->forceDelete();
        IngredientCategory::query()->delete();

        $categories = [
            'Daging Olahan' => [
                ['name' => 'beef paties', 'min' => 20, 'price' => 70000, 'stock' => 103, 'pack_size' => 10],
                ['name' => 'chiken', 'min' => 20, 'price' => 50000, 'stock' => 154, 'pack_size' => 10],
                ['name' => 'sosis jumbo', 'min' => 16, 'price' => 80000, 'stock' => 43, 'pack_size' => 10],
                ['name' => 'sosis kecil', 'min' => 32, 'price' => 48000, 'stock' => 88, 'pack_size' => 20],
            ],
            'Roti & Tortila' => [
                ['name' => 'roti burger', 'min' => 12, 'price' => 30000, 'stock' => 2, 'pack_size' => 10],
                ['name' => 'roti pizza', 'min' => 8, 'price' => 12000, 'stock' => 4, 'pack_size' => 10],
                ['name' => 'tortila besar', 'min' => 40, 'price' => 30000, 'stock' => 470, 'pack_size' => 20],
                ['name' => 'tortila sedang', 'min' => 40, 'price' => 24000, 'stock' => 507, 'pack_size' => 20],
                ['name' => 'tortila mini', 'min' => 40, 'price' => 20000, 'stock' => 283, 'pack_size' => 20],
            ],
            'Keju & Telur' => [
                ['name' => 'keju parut', 'min' => 24, 'price' => 36000, 'stock' => 81, 'pack_size' => 1, 'unit' => 'kg'],
                ['name' => 'keju slice', 'min' => 12, 'price' => 36000, 'stock' => 51, 'pack_size' => 10],
                ['name' => 'mozzarella', 'min' => 24, 'price' => 60000, 'stock' => 19, 'pack_size' => 1, 'unit' => 'kg'],
                ['name' => 'Telor', 'min' => 1, 'price' => 3000, 'stock' => 13, 'pack_size' => 1],
            ],
            'Saus & Bumbu' => [
                ['name' => 'Saus Sambal', 'min' => 5, 'price' => 25000, 'stock' => 12, 'pack_size' => 1, 'unit' => 'l'],
                ['name' => 'Saus Tomat', 'min' => 5, 'price' => 25000, 'stock' => 8, 'pack_size' => 1, 'unit' => 'l'],
                ['name' => 'Mayonnaise', 'min' => 5, 'price' => 35000, 'stock' => 10, 'pack_size' => 1, 'unit' => 'l'],
                ['name' => 'Saus Barbeque', 'min' => 3, 'price' => 40000, 'stock' => 5, 'pack_size' => 1, 'unit' => 'l'],
                ['name' => 'Saus Blackpepper', 'min' => 3, 'price' => 45000, 'stock' => 4, 'pack_size' => 1, 'unit' => 'l'],
            ],
            'Lainnya' => [
                ['name' => 'Sayur Selada', 'min' => 2, 'price' => 15000, 'stock' => 5, 'pack_size' => 1, 'unit' => 'kg'],
                ['name' => 'Bawang Bombay', 'min' => 2, 'price' => 20000, 'stock' => 4.5, 'pack_size' => 1, 'unit' => 'kg'],
                ['name' => 'Margarin', 'min' => 2, 'price' => 20000, 'stock' => 6, 'pack_size' => 1, 'unit' => 'kg'],
                ['name' => 'Kertas Pembungkus Kebab', 'min' => 100, 'price' => 25000, 'stock' => 450, 'pack_size' => 100, 'unit' => 'pcs'],
                ['name' => 'Kantong Plastik', 'min' => 5, 'price' => 10000, 'stock' => 15, 'pack_size' => 1, 'unit' => 'pcs'],
            ]
        ];

        foreach ($categories as $catName => $ingredients) {
            $category = IngredientCategory::firstOrCreate(['name' => $catName]);

            foreach ($ingredients as $item) {
                $rawUnit = $item['unit'] ?? 'pcs';
                
                // Tentukan base_unit dan display_unit yang benar sesuai sistem
                $baseUnit = 'pcs';
                $displayUnit = 'pcs';
                $stockInBase = $item['stock'];
                $minInBase = $item['min'];
                
                if ($rawUnit === 'kg') {
                    $baseUnit = 'g';
                    $displayUnit = 'kg';
                    $stockInBase = $item['stock'] * 1000;
                    $minInBase = $item['min'] * 1000;
                } elseif ($rawUnit === 'l') {
                    $baseUnit = 'ml';
                    $displayUnit = 'l';
                    $stockInBase = $item['stock'] * 1000;
                    $minInBase = $item['min'] * 1000;
                }

                $costPerBaseUnit = $item['price'] / $item['pack_size'];
                // HPP / Modal = costPerBaseUnit. Harga Jual = Modal + 50% Margin
                $sellPrice = ceil(($costPerBaseUnit * 1.5) / 100) * 100; // Bulatkan ke kelipatan 100

                Ingredient::firstOrCreate(
                    ['name' => $item['name'], 'category_id' => $category->id],
                    [
                        'display_unit' => $displayUnit,
                        'base_unit' => $baseUnit,
                        'pack_size' => $item['pack_size'],
                        'stock' => $stockInBase,
                        'minimum_stock' => $minInBase,
                        'selling_price' => $sellPrice,
                        'cost_price' => $costPerBaseUnit,
                    ]
                );
            }
        }
    }
}
