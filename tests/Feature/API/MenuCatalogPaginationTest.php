<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuCatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cashier_catalog_paginates_available_variants_on_the_server(): void
    {
        [$cashier, $token, $session, $ingredient] = $this->cashierContext();
        $firstCategory = MenuCategory::query()->create(['name' => 'Kebab']);
        $secondCategory = MenuCategory::query()->create(['name' => 'Burger']);

        foreach (range(1, 5) as $number) {
            $this->createVariant(
                category: $number <= 3 ? $firstCategory : $secondCategory,
                ingredient: $ingredient,
                number: $number,
            );
        }

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 100,
            'remaining_qty' => 100,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $firstPage = $this->withToken($token)->getJson('/api/menus?page=1&per_page=2');
        $firstPage
            ->assertOk()
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.last_page', 3)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.total', 5)
            ->assertJsonPath('data.pagination.has_more', true);

        $firstIds = $this->variantIds($firstPage->json('data.menus'));
        $this->assertCount(2, $firstIds);
        $this->assertArrayNotHasKey(
            'required_ingredients',
            $firstPage->json('data.menus.0.variants.0')
        );

        $secondPage = $this->withToken($token)->getJson('/api/menus?page=2&per_page=2');
        $secondIds = $this->variantIds($secondPage->json('data.menus'));

        $secondPage
            ->assertOk()
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.has_more', true);
        $this->assertCount(2, $secondIds);
        $this->assertSame([], array_values(array_intersect($firstIds, $secondIds)));
        $this->assertCount(2, $firstPage->json('data.categories'));
    }

    public function test_category_search_and_per_page_limit_are_applied_before_pagination(): void
    {
        [, $token, $session, $ingredient] = $this->cashierContext();
        $kebab = MenuCategory::query()->create(['name' => 'Kebab']);
        $burger = MenuCategory::query()->create(['name' => 'Burger']);
        $this->createVariant($kebab, $ingredient, 1, 'Original');
        $this->createVariant($kebab, $ingredient, 2, 'Jumbo');
        $this->createVariant($burger, $ingredient, 3, 'Original');

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 100,
            'remaining_qty' => 100,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $this->withToken($token)
            ->getJson('/api/menus?category_id='.$kebab->id.'&search=Jumbo&page=1&per_page=999')
            ->assertOk()
            ->assertJsonPath('data.filters.category_id', $kebab->id)
            ->assertJsonPath('data.filters.search', 'Jumbo')
            ->assertJsonPath('data.pagination.per_page', 50)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.menus.0.variants.0.name', 'Jumbo');
    }

    /**
     * @return array{User, string, DailyStockSession, Ingredient}
     */
    private function cashierContext(): array
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'CAT'],
            ['name' => 'Catalog Branch', 'is_active' => true],
        );
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $token = 'catalog_'.bin2hex(random_bytes(12));

        ApiToken::query()->create([
            'user_id' => $cashier->id,
            'name' => 'catalog-test',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $session = DailyStockSession::query()->create([
            'session_date' => now('Asia/Jakarta')->toDateString(),
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'branch_id' => $branch->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $ingredient = Ingredient::query()->create([
            'name' => 'Catalog Ingredient',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
            'selling_price' => 1000,
        ]);

        return [$cashier, $token, $session, $ingredient];
    }

    private function createVariant(
        MenuCategory $category,
        Ingredient $ingredient,
        int $number,
        string $variantName = 'Regular',
    ): MenuVariant {
        $menu = Menu::query()->create([
            'category_id' => $category->id,
            'name' => 'Menu '.$number,
            'description' => null,
            'is_active' => true,
            'sort_order' => $number,
        ]);
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => $variantName,
            'price' => 10_000 + $number,
            'is_available' => true,
            'sort_order' => 0,
        ]);
        $variant->ingredients()->attach($ingredient->id, ['quantity' => 1]);

        return $variant;
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @return array<int, int>
     */
    private function variantIds(array $menus): array
    {
        return collect($menus)
            ->flatMap(fn (array $menu) => collect($menu['variants'])->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
