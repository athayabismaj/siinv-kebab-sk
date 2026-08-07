<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuCategory;
use App\Models\Menu;
use App\Models\MenuVariant;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data lama secara permanen (karena Menu ada fitur SoftDeletes)
        MenuVariant::query()->delete();
        Menu::query()->forceDelete();
        MenuCategory::query()->delete();

        $menuStructure = [
            [
                'category' => 'Kebab Original',
                'menu_name' => 'Kebab Original',
                'is_addon' => false,
                'variants' => [
                    ['name' => 'Kebab Mini', 'price' => 10000],
                    ['name' => 'Kebab Sedang', 'price' => 12000],
                    ['name' => 'Kebab Jumbo', 'price' => 15000],
                    ['name' => 'Kebab Barbeque', 'price' => 13000],
                    ['name' => 'Kebab Blackpaper', 'price' => 13000],
                    ['name' => 'Kebab Double Beef', 'price' => 17000],
                    ['name' => 'Kebab Mozarella', 'price' => 17000],
                    ['name' => 'Kebab Double Mozarella', 'price' => 22000],
                ]
            ],
            [
                'category' => 'Sosis Jumbo',
                'menu_name' => 'Sosis Jumbo',
                'is_addon' => false,
                'variants' => [
                    ['name' => 'Sosis Jumbo Original', 'price' => 10000],
                    ['name' => 'Sosis Jumbo Barbeque', 'price' => 12000],
                    ['name' => 'Sosis Jumbo Blackpaper', 'price' => 12000],
                    ['name' => 'Sosis Jumbo Mozarella', 'price' => 15000],
                ]
            ],
            [
                'category' => 'Burger',
                'menu_name' => 'Burger',
                'is_addon' => false,
                'variants' => [
                    ['name' => 'Burger Chiken', 'price' => 10000],
                    ['name' => 'Burger Beef Paties', 'price' => 12000],
                    ['name' => 'Burger Chiken Telor', 'price' => 13000],
                    ['name' => 'Burger Beef Telor', 'price' => 15000],
                    ['name' => 'Burger Sosis jumbo', 'price' => 15000],
                    ['name' => 'Burger Double Chiken', 'price' => 15000],
                    ['name' => 'Burger Double Beef Paties', 'price' => 19000],
                    ['name' => 'Burger Special Keju', 'price' => 20000],
                    ['name' => 'Burger Special Mozarella', 'price' => 25000],
                ]
            ],
            [
                'category' => 'Pizza',
                'menu_name' => 'Pizza',
                'is_addon' => false,
                'variants' => [
                    ['name' => 'Pizza Sosis Keju', 'price' => 15000],
                    ['name' => 'Pizza Telor Keju', 'price' => 15000],
                    ['name' => 'Pizza Beef Keju', 'price' => 20000],
                    ['name' => 'Pizza Chiken Keju', 'price' => 20000],
                    ['name' => 'Pizza Chiken + Beef Keju', 'price' => 25000],
                    ['name' => 'Pizza Sosis Jumbo Keju', 'price' => 25000],
                    ['name' => 'Pizza Spesial Keju', 'price' => 25000],
                    ['name' => 'Pizza Spesial Mozarella', 'price' => 30000],
                    ['name' => 'Pizza special Double Mozarella', 'price' => 35000],
                ]
            ],
            [
                'category' => 'Add On',
                'menu_name' => 'Tambah Toping',
                'is_addon' => true,
                'variants' => [
                    ['name' => 'Sosis', 'price' => 3000],
                    ['name' => 'Telor', 'price' => 3000],
                    ['name' => 'Mozarella', 'price' => 5000],
                    ['name' => 'Chiken', 'price' => 5000],
                ]
            ]
        ];

        foreach ($menuStructure as $index => $data) {
            // 1. Buat Kategori
            $category = MenuCategory::firstOrCreate(
                ['name' => $data['category']],
                ['is_addon' => $data['is_addon']]
            );

            // 2. Buat Induk Menu (Hanya 1 per kategori)
            $menu = Menu::firstOrCreate(
                ['name' => $data['menu_name'], 'category_id' => $category->id],
                [
                    'is_active' => true,
                    'sort_order' => $index + 1
                ]
            );

            // 3. Masukkan semua varian ke dalam menu tersebut
            foreach ($data['variants'] as $vIndex => $variantData) {
                // HPP F&B umumnya 35% - 40% dari harga jual. Kita set ~35% dibulatkan ke kelipatan 500 terdekat.
                $costPrice = ceil(($variantData['price'] * 0.35) / 500) * 500;

                MenuVariant::firstOrCreate(
                    ['menu_id' => $menu->id, 'name' => $variantData['name']],
                    [
                        'price' => $variantData['price'],
                        'sell_price' => $variantData['price'],
                        'cost_price' => $costPrice,
                        'is_available' => true,
                        'sort_order' => $vIndex + 1
                    ]
                );
            }
        }
    }
}
