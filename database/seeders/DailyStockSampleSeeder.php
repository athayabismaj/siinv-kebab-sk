<?php

namespace Database\Seeders;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DailyStockSampleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['name' => 'kasir']);

        $admin = User::updateOrCreate(
            ['username' => 'admin_daily_sample'],
            [
                'name' => 'Admin Daily Sample',
                'email' => 'admin-daily-sample@example.test',
                'password' => 'secret123',
                'role_id' => $adminRole->id,
            ]
        );

        $cashier = User::updateOrCreate(
            ['username' => 'kasir_daily_sample'],
            [
                'name' => 'Kasir Daily Sample',
                'email' => 'kasir-daily-sample@example.test',
                'password' => 'secret123',
                'role_id' => $cashierRole->id,
            ]
        );

        $category = IngredientCategory::firstOrCreate(['name' => 'Sample Daily Stock']);

        $ingredientA = Ingredient::updateOrCreate(
            ['name' => 'Tortilla Mini'],
            [
                'category_id' => $category->id,
                'display_unit' => 'pcs',
                'base_unit' => 'pcs',
                'pack_size' => 20,
                'stock' => 250,
                'minimum_stock' => 40,
            ]
        );

        $ingredientB = Ingredient::updateOrCreate(
            ['name' => 'Sosis Jumbo'],
            [
                'category_id' => $category->id,
                'display_unit' => 'pcs',
                'base_unit' => 'pcs',
                'pack_size' => 7,
                'stock' => 140,
                'minimum_stock' => 21,
            ]
        );

        $sessionDate = Carbon::today()->toDateString();

        $session = DailyStockSession::updateOrCreate(
            [
                'session_date' => $sessionDate,
                'cashier_id' => $cashier->id,
            ],
            [
                'opened_by' => $admin->id,
                'status' => 'open',
                'opened_at' => now(),
                'notes' => 'Sample session untuk uji cepat stok harian',
            ]
        );

        $itemA = DailyStockItem::updateOrCreate(
            [
                'daily_stock_session_id' => $session->id,
                'ingredient_id' => $ingredientA->id,
            ],
            [
                'opening_qty' => 40,
                'remaining_qty' => 40,
                'used_qty' => 0,
                'returned_qty' => 0,
                'note' => 'Bawa pagi',
            ]
        );

        $itemB = DailyStockItem::updateOrCreate(
            [
                'daily_stock_session_id' => $session->id,
                'ingredient_id' => $ingredientB->id,
            ],
            [
                'opening_qty' => 14,
                'remaining_qty' => 14,
                'used_qty' => 0,
                'returned_qty' => 0,
                'note' => 'Bawa pagi',
            ]
        );

        StockLog::firstOrCreate(
            [
                'ingredient_id' => $ingredientA->id,
                'type' => 'transfer_daily',
                'reference_id' => $session->id,
                'quantity' => -40,
            ],
            [
                'note' => 'Sample transfer daily',
            ]
        );

        StockLog::firstOrCreate(
            [
                'ingredient_id' => $ingredientB->id,
                'type' => 'transfer_daily',
                'reference_id' => $session->id,
                'quantity' => -14,
            ],
            [
                'note' => 'Sample transfer daily',
            ]
        );
    }
}

