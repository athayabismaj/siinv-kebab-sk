<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\View\Navigation\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AdminDataLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingredient_index_supports_lifecycle_filters_counts_and_pagination(): void
    {
        $admin = $this->admin();
        $category = IngredientCategory::query()->create(['name' => 'Bahan Utama']);

        $active = $this->ingredient('Target Aktif', $category);
        $archived = $this->ingredient('Target Arsip', $category);
        $archived->delete();

        for ($index = 1; $index <= 10; $index++) {
            $this->ingredient('Target Tambahan ' . $index, $category);
        }

        $defaultResponse = $this->actingAs($admin)->get(route('admin.ingredients.index'));
        $defaultResponse->assertOk()->assertViewHas('recordStatus', 'active');
        $defaultResponse->assertViewHas('ingredients', function (LengthAwarePaginator $ingredients) use ($archived): bool {
            return $ingredients->perPage() === 10
                && $ingredients->total() === 11
                && ! $ingredients->getCollection()->contains('id', $archived->id);
        });

        $allResponse = $this->actingAs($admin)->get(route('admin.ingredients.index', [
            'record_status' => 'all',
            'search' => 'Target',
            'category' => $category->id,
        ]));

        $allResponse->assertOk()
            ->assertViewHas('recordStatus', 'all')
            ->assertViewHas('activeCount', 11)
            ->assertViewHas('archivedCount', 1)
            ->assertViewHas('allCount', 12);

        $allResponse->assertViewHas('ingredients', function (LengthAwarePaginator $ingredients) use ($archived): bool {
            return $ingredients->perPage() === 10
                && $ingredients->total() === 12
                && ! $ingredients->getCollection()->contains('id', $archived->id);
        });

        $pageTwo = $this->actingAs($admin)->get(route('admin.ingredients.index', [
            'record_status' => 'all',
            'search' => 'Target',
            'category' => $category->id,
            'page' => 2,
        ]));

        $pageTwo->assertOk()
            ->assertSee('Target Aktif')
            ->assertSee('Target Arsip')
            ->assertSee('record_status=all', false)
            ->assertSee('search=Target', false)
            ->assertSee('category=' . $category->id, false);
    }

    public function test_menu_index_keeps_lifecycle_separate_from_sales_availability(): void
    {
        $admin = $this->admin();
        $category = MenuCategory::query()->create(['name' => 'Makanan']);

        $available = $this->menu('Kebab Tersedia', $category, true, 1);
        $unavailable = $this->menu('Kebab Tidak Tersedia', $category, false, 2);
        $archived = $this->menu('Kebab Arsip', $category, true, 3);
        $archived->delete();

        MenuVariant::query()->create([
            'menu_id' => $available->id,
            'name' => 'Mini',
            'price' => 10000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.menus.index', [
            'record_status' => 'all',
            'search' => 'Kebab',
            'category' => $category->id,
        ]));

        $response->assertOk()
            ->assertViewHas('recordStatus', 'all')
            ->assertViewHas('activeCount', 2)
            ->assertViewHas('archivedCount', 1)
            ->assertViewHas('allCount', 3)
            ->assertViewHas('categories')
            ->assertSee('Kebab Tersedia')
            ->assertSee('Kebab Tidak Tersedia')
            ->assertSee('Kebab Arsip')
            ->assertSee('Tersedia')
            ->assertSee('Tidak Tersedia')
            ->assertSee('Diarsipkan')
            ->assertSee('1 Varian');

        $this->assertTrue($unavailable->fresh()->is_active === false);
        $this->assertTrue(Menu::withTrashed()->findOrFail($archived->id)->is_active);
    }

    public function test_legacy_archive_routes_redirect_to_the_unified_archived_filter(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.ingredients.archive', [
                'search' => 'saus',
                'category' => 7,
                'has_price' => '1',
                'unsafe' => 'discarded',
            ]))
            ->assertRedirect(route('admin.ingredients.index', [
                'search' => 'saus',
                'category' => 7,
                'has_price' => '1',
                'record_status' => 'archived',
            ]));

        $this->actingAs($admin)
            ->get(route('admin.menus.archive', [
                'search' => 'kebab',
                'category' => 9,
                'unsafe' => 'discarded',
            ]))
            ->assertRedirect(route('admin.menus.index', [
                'search' => 'kebab',
                'category' => 9,
                'record_status' => 'archived',
            ]));
    }

    public function test_ingredient_soft_delete_and_restore_preserve_recipe_and_stock_history(): void
    {
        $admin = $this->admin();
        $branch = $this->branch();
        $cashier = $this->cashier($branch);
        $ingredient = $this->ingredient('Daging Historis');
        $menu = $this->menu('Menu Historis');
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Reguler',
            'price' => 15000,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        $variant->ingredients()->attach($ingredient->id, ['quantity' => 50]);

        $stockLog = StockLog::query()->create([
            'branch_id' => $branch->id,
            'ingredient_id' => $ingredient->id,
            'type' => 'in',
            'quantity' => 100,
            'note' => 'Riwayat stok',
        ]);
        $session = DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $dailyItem = DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.ingredients.destroy', $ingredient))
            ->assertRedirect(route('admin.ingredients.index'));

        $this->assertSoftDeleted('ingredients', ['id' => $ingredient->id]);
        $this->assertDatabaseHas('menu_variant_ingredients', [
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 50,
        ]);
        $this->assertDatabaseHas('stock_logs', ['id' => $stockLog->id, 'ingredient_id' => $ingredient->id]);
        $this->assertDatabaseHas('daily_stock_items', ['id' => $dailyItem->id, 'ingredient_id' => $ingredient->id]);

        $this->actingAs($admin)
            ->patch(route('admin.ingredients.restore', $ingredient->id))
            ->assertRedirect(route('admin.ingredients.index', ['record_status' => 'active']));

        $this->assertNotSoftDeleted('ingredients', ['id' => $ingredient->id]);
    }

    public function test_menu_soft_delete_and_restore_preserve_variants_transactions_and_availability(): void
    {
        $admin = $this->admin();
        $branch = $this->branch();
        $cashier = $this->cashier($branch);
        $menu = $this->menu('Burger Historis', null, false);
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Single',
            'price' => 12000,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-HISTORIS-001',
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'total_amount' => 12000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 12000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
        $detail = TransactionDetail::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 12000,
            'subtotal' => 12000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.menus.destroy', $menu))
            ->assertRedirect(route('admin.menus.index'));

        $this->assertSoftDeleted('menus', ['id' => $menu->id]);
        $this->assertDatabaseHas('menu_variants', ['id' => $variant->id, 'menu_id' => $menu->id]);
        $this->assertDatabaseHas('transaction_details', ['id' => $detail->id, 'menu_id' => $menu->id]);
        $this->assertFalse(Menu::withTrashed()->findOrFail($menu->id)->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.menus.restore', $menu->id))
            ->assertRedirect(route('admin.menus.index', ['record_status' => 'active']));

        $this->assertNotSoftDeleted('menus', ['id' => $menu->id]);
        $this->assertFalse($menu->fresh()->is_active);
    }

    public function test_archived_records_cannot_be_edited_and_restore_only_accepts_archived_records(): void
    {
        $admin = $this->admin();
        $ingredient = $this->ingredient('Bahan Arsip Aman');
        $menu = $this->menu('Menu Arsip Aman');
        $activeIngredient = $this->ingredient('Bahan Aktif Aman');
        $activeMenu = $this->menu('Menu Aktif Aman');
        $ingredient->delete();
        $menu->delete();

        $this->actingAs($admin)->get(route('admin.ingredients.edit', $ingredient->id))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.menus.edit', $menu->id))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.ingredients.restore', $activeIngredient->id))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.menus.restore', $activeMenu->id))->assertNotFound();
    }

    public function test_lifecycle_management_remains_admin_only(): void
    {
        $owner = $this->userForRole('owner');
        $ingredient = $this->ingredient('Bahan Admin');
        $menu = $this->menu('Menu Admin');
        $ingredient->delete();
        $menu->delete();

        $this->actingAs($owner)->get(route('admin.ingredients.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.menus.index'))->assertForbidden();
        $this->actingAs($owner)->patch(route('admin.ingredients.restore', $ingredient->id))->assertForbidden();
        $this->actingAs($owner)->patch(route('admin.menus.restore', $menu->id))->assertForbidden();
    }

    public function test_admin_navigation_and_assets_no_longer_expose_standalone_archives(): void
    {
        $labels = collect((new AdminNavigation)->sections())->pluck('label');

        $this->assertNotContains('Arsip Data', $labels);
        $this->assertFileDoesNotExist(resource_path('views/admin/ingredients/archive.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/admin/menus/archive.blade.php'));
        $this->assertFileDoesNotExist(resource_path('css/pages/admin-archive.css'));
        $this->assertStringNotContainsString(
            'resources/css/pages/admin-archive.css',
            (string) file_get_contents(base_path('vite.config.js'))
        );
    }

    private function admin(): User
    {
        return $this->userForRole('admin');
    }

    private function cashier(Branch $branch): User
    {
        return $this->userForRole('kasir', $branch->id);
    }

    private function userForRole(string $roleName, ?int $branchId = null): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branchId,
        ]);
    }

    private function branch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Cabang Lifecycle',
            'code' => 'LFC',
            'is_active' => true,
        ]);
    }

    private function ingredient(string $name, ?IngredientCategory $category = null): Ingredient
    {
        return Ingredient::query()->create([
            'category_id' => $category?->id,
            'name' => $name,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 10,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
    }

    private function menu(
        string $name,
        ?MenuCategory $category = null,
        bool $isActive = true,
        int $sortOrder = 0,
    ): Menu {
        return Menu::query()->create([
            'category_id' => $category?->id,
            'name' => $name,
            'description' => null,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ]);
    }
}
