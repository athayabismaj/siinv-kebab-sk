<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuVariant;
use App\Models\Ingredient;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = Ingredient::all()->keyBy('name');

        // Fungsi bantu untuk mengambil ID bahan
        $ingId = function ($name) use ($ingredients) {
            return $ingredients->get($name)?->id;
        };

        $variants = MenuVariant::with('menu.category')->get();

        foreach ($variants as $variant) {
            $recipe = [];
            $name = strtolower($variant->name);
            $cat = strtolower($variant->menu->category->name);

            // --- UMUM (Packaging & Basic Saus) ---
            if ($cat !== 'add on') {
                if ($cat === 'kebab original') {
                    $recipe[$ingId('Kertas Pembungkus Kebab')] = ['quantity' => 1];
                }
                
                // Sayuran dasar
                if (in_array($cat, ['kebab original', 'burger'])) {
                    $recipe[$ingId('Sayur Selada')] = ['quantity' => 15]; // 15 gram
                    $recipe[$ingId('Bawang Bombay')] = ['quantity' => 10]; // 10 gram
                    $recipe[$ingId('Mayonnaise')] = ['quantity' => 15]; // 15 ml
                    $recipe[$ingId('Saus Tomat')] = ['quantity' => 10]; // 10 ml
                    $recipe[$ingId('Margarin')] = ['quantity' => 10]; // 10 gram
                }
            }

            // --- KEBAB ORIGINAL ---
            if ($cat === 'kebab original') {
                if (str_contains($name, 'mini')) {
                    $recipe[$ingId('tortila mini')] = ['quantity' => 1];
                    $recipe[$ingId('beef paties')] = ['quantity' => 1];
                } elseif (str_contains($name, 'jumbo')) {
                    $recipe[$ingId('tortila besar')] = ['quantity' => 1];
                    $recipe[$ingId('beef paties')] = ['quantity' => 2];
                } else {
                    $recipe[$ingId('tortila sedang')] = ['quantity' => 1];
                    $recipe[$ingId('beef paties')] = ['quantity' => 1];
                }

                if (str_contains($name, 'double beef')) {
                    $recipe[$ingId('beef paties')] = ['quantity' => 2];
                }
                if (str_contains($name, 'barbeque')) {
                    $recipe[$ingId('Saus Barbeque')] = ['quantity' => 20];
                }
                if (str_contains($name, 'blackpaper')) {
                    $recipe[$ingId('Saus Blackpepper')] = ['quantity' => 20];
                }
                if (str_contains($name, 'mozarella')) {
                    $quantity = str_contains($name, 'double') ? 40 : 20; // gram
                    $recipe[$ingId('mozzarella')] = ['quantity' => $quantity];
                }
            }

            // --- SOSIS JUMBO ---
            if ($cat === 'sosis jumbo') {
                $recipe[$ingId('sosis jumbo')] = ['quantity' => 1];
                $recipe[$ingId('Kertas Pembungkus Kebab')] = ['quantity' => 1];
                $recipe[$ingId('Margarin')] = ['quantity' => 15];
                
                if (str_contains($name, 'barbeque')) {
                    $recipe[$ingId('Saus Barbeque')] = ['quantity' => 25];
                } elseif (str_contains($name, 'blackpaper')) {
                    $recipe[$ingId('Saus Blackpepper')] = ['quantity' => 25];
                } else {
                    $recipe[$ingId('Saus Sambal')] = ['quantity' => 15];
                    $recipe[$ingId('Mayonnaise')] = ['quantity' => 15];
                }

                if (str_contains($name, 'mozarella')) {
                    $recipe[$ingId('mozzarella')] = ['quantity' => 25];
                }
            }

            // --- BURGER ---
            if ($cat === 'burger') {
                $recipe[$ingId('roti burger')] = ['quantity' => 1];

                if (str_contains($name, 'chiken')) {
                    $recipe[$ingId('chiken')] = str_contains($name, 'double') ? ['quantity' => 2] : ['quantity' => 1];
                } else {
                    $recipe[$ingId('beef paties')] = str_contains($name, 'double') ? ['quantity' => 2] : ['quantity' => 1];
                }

                if (str_contains($name, 'telor')) {
                    $recipe[$ingId('Telor')] = ['quantity' => 1];
                }
                if (str_contains($name, 'sosis')) {
                    $recipe[$ingId('sosis jumbo')] = ['quantity' => 1];
                    unset($recipe[$ingId('beef paties')]); // Ganti beef dengan sosis
                }

                if (str_contains($name, 'keju')) {
                    $recipe[$ingId('keju slice')] = ['quantity' => 1];
                }
                if (str_contains($name, 'mozarella')) {
                    $recipe[$ingId('mozzarella')] = ['quantity' => 25];
                }
            }

            // --- PIZZA ---
            if ($cat === 'pizza') {
                $recipe[$ingId('roti pizza')] = ['quantity' => 1];
                $recipe[$ingId('Saus Tomat')] = ['quantity' => 25]; // saus dasar
                $recipe[$ingId('Margarin')] = ['quantity' => 10];
                $recipe[$ingId('keju parut')] = ['quantity' => 15];
                
                if (str_contains($name, 'sosis') && !str_contains($name, 'jumbo')) {
                    $recipe[$ingId('sosis kecil')] = ['quantity' => 1];
                }
                if (str_contains($name, 'sosis jumbo')) {
                    $recipe[$ingId('sosis jumbo')] = ['quantity' => 1];
                }
                if (str_contains($name, 'telor')) {
                    $recipe[$ingId('Telor')] = ['quantity' => 1];
                }
                if (str_contains($name, 'beef')) {
                    $recipe[$ingId('beef paties')] = ['quantity' => 1];
                }
                if (str_contains($name, 'chiken')) {
                    $recipe[$ingId('chiken')] = ['quantity' => 1];
                }
                
                if (str_contains($name, 'keju')) {
                    $recipe[$ingId('keju slice')] = ['quantity' => 1];
                }
                if (str_contains($name, 'mozarella')) {
                    $quantity = str_contains($name, 'double') ? 50 : 30; // gram
                    $recipe[$ingId('mozzarella')] = ['quantity' => $quantity];
                }
            }

            // --- ADD ON ---
            if ($cat === 'add on') {
                if (str_contains($name, 'sosis')) {
                    $recipe[$ingId('sosis kecil')] = ['quantity' => 1];
                }
                if (str_contains($name, 'telor')) {
                    $recipe[$ingId('Telor')] = ['quantity' => 1];
                }
                if (str_contains($name, 'mozarella')) {
                    $recipe[$ingId('mozzarella')] = ['quantity' => 20]; // 20 gram
                }
                if (str_contains($name, 'chiken')) {
                    $recipe[$ingId('chiken')] = ['quantity' => 1];
                }
            }

            // Hapus nilai null (jika bahan tidak ditemukan)
            $recipe = array_filter($recipe, fn($key) => $key !== null, ARRAY_FILTER_USE_KEY);

            // Sync ke database
            if (!empty($recipe)) {
                $variant->ingredients()->sync($recipe);
            }
        }
    }
}
