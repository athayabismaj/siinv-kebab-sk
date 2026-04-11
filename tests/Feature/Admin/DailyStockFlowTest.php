<?php

namespace Tests\Feature\Admin;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_stock_successfully_reduces_warehouse_stock(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset();

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 20,
        ])->assertRedirect();

        $ingredient->refresh();
        $this->assertSame(80.0, (float) $ingredient->stock);

        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 20.00,
            'remaining_qty' => 20.00,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'transfer_daily',
            'quantity' => -20.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_transfer_fails_when_warehouse_stock_is_insufficient(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 10);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.daily-stocks.index', [
                'date' => now()->toDateString(),
                'cashier_id' => $cashier->id,
            ]))
            ->post(route('admin.daily-stocks.transfer'), [
                'session_id' => $session->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => 50,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $ingredient->refresh();
        $this->assertSame(10.0, (float) $ingredient->stock);
        $this->assertDatabaseCount('daily_stock_items', 0);
        $this->assertDatabaseCount('stock_logs', 0);
    }

    public function test_close_session_calculates_usage_correctly(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 100);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 30,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.daily-stocks.close'), [
            'session_id' => $session->id,
            'remaining' => [
                $ingredient->id => 10,
            ],
        ])->assertRedirect();

        $session->refresh();
        $item = DailyStockItem::query()->firstOrFail();
        $ingredient->refresh();

        $this->assertSame('closed', $session->status);
        $this->assertSame(30.0, (float) $item->opening_qty);
        $this->assertSame(10.0, (float) $item->remaining_qty);
        $this->assertSame(20.0, (float) $item->used_qty);
        $this->assertSame(10.0, (float) $item->returned_qty);

        // 100 - 30 transfer + 10 return = 80
        $this->assertSame(80.0, (float) $ingredient->stock);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -20.00,
            'reference_id' => $session->id,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_return',
            'quantity' => 10.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_admin_can_queue_daily_stock_report_export(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset();

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 10,
        ])->assertRedirect();

        $response = $this->actingAs($admin)->get(route('admin.reports.daily-stock.export', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertRedirect(route('admin.exports.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('report_exports', [
            'requested_by' => $admin->id,
            'scope' => 'admin',
            'type' => 'admin.daily_stock',
        ]);

        $export = ReportExport::query()->latest('id')->firstOrFail();
        $this->assertContains($export->status, ['queued', 'processing', 'completed']);
    }

    /**
     * @return array{0: User, 1: User, 2: Ingredient}
     */
    private function baseDailyStockDataset(float $stock = 100): array
    {
        $adminRole = Role::create(['name' => 'admin']);
        $cashierRole = Role::create(['name' => 'kasir']);

        $admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'admin_uji',
            'email' => 'admin-uji@example.test',
            'password' => 'secret123',
            'role_id' => $adminRole->id,
        ]);

        $cashier = User::create([
            'name' => 'Kasir Uji',
            'username' => 'kasir_uji',
            'email' => 'kasir-uji@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
        ]);

        $category = IngredientCategory::create(['name' => 'Bahan Uji']);
        $ingredient = Ingredient::create([
            'category_id' => $category->id,
            'name' => 'Tortilla',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => $stock,
            'minimum_stock' => 5,
        ]);

        return [$admin, $cashier, $ingredient];
    }
}
