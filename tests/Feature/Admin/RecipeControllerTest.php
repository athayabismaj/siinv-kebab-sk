<?php

namespace Tests\Feature\Admin;

use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_and_search_recipe_index(): void
    {
        $admin = $this->createAdminUser();

        $categoryA = MenuCategory::create(['name' => 'Kategori A']);
        $categoryB = MenuCategory::create(['name' => 'Kategori B']);

        $menuA = Menu::create([
            'category_id' => $categoryA->id,
            'name' => 'Kebab Original',
            'description' => null,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $menuB = Menu::create([
            'category_id' => $categoryB->id,
            'name' => 'Burger Spesial',
            'description' => null,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        MenuVariant::create([
            'menu_id' => $menuA->id,
            'name' => 'Regular',
            'price' => 12000,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        MenuVariant::create([
            'menu_id' => $menuB->id,
            'name' => 'Regular',
            'price' => 15000,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.recipes.index', [
            'search' => 'kebab',
            'category' => $categoryA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Kebab Original');
        $response->assertDontSee('Burger Spesial');
    }

    public function test_admin_can_update_recipe_successfully(): void
    {
        $admin = $this->createAdminUser();
        [$variant, $ingredientA, $ingredientB] = $this->createRecipeDataset();

        $variant->ingredients()->attach($ingredientA->id, ['quantity' => 1.00]);

        $response = $this->actingAs($admin)->put(route('admin.recipes.update', $variant->id), [
            'ingredients' => [
                (string) $ingredientA->id => 2.50,
                (string) $ingredientB->id => 0,
            ],
        ]);

        $response->assertRedirect(route('admin.recipes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('menu_variant_ingredients', [
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredientA->id,
            'quantity' => 2.50,
        ]);

        $this->assertDatabaseMissing('menu_variant_ingredients', [
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredientB->id,
        ]);
    }

    public function test_update_recipe_rejects_invalid_ingredient_id(): void
    {
        $admin = $this->createAdminUser();
        [$variant] = $this->createRecipeDataset();

        $response = $this->actingAs($admin)->from(route('admin.recipes.edit', $variant->id))
            ->put(route('admin.recipes.update', $variant->id), [
                'ingredients' => [
                    '999999' => 1.25,
                ],
            ]);

        $response->assertRedirect(route('admin.recipes.edit', $variant->id));
        $response->assertSessionHasErrors('ingredients');
    }

    public function test_update_recipe_requires_minimum_one_positive_quantity(): void
    {
        $admin = $this->createAdminUser();
        [$variant, $ingredientA, $ingredientB] = $this->createRecipeDataset();

        $response = $this->actingAs($admin)->from(route('admin.recipes.edit', $variant->id))
            ->put(route('admin.recipes.update', $variant->id), [
                'ingredients' => [
                    (string) $ingredientA->id => 0,
                    (string) $ingredientB->id => 0,
                ],
            ]);

        $response->assertRedirect(route('admin.recipes.edit', $variant->id));
        $response->assertSessionHasErrors('ingredients');
    }

    private function createAdminUser(): User
    {
        $role = Role::create(['name' => 'admin']);

        return User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
        ]);
    }

    /**
     * @return array{0: MenuVariant, 1: Ingredient, 2: Ingredient}
     */
    private function createRecipeDataset(): array
    {
        $menuCategory = MenuCategory::create(['name' => 'Makanan']);
        $ingredientCategory = IngredientCategory::create(['name' => 'Bahan Utama']);

        $menu = Menu::create([
            'category_id' => $menuCategory->id,
            'name' => 'Kebab Uji',
            'description' => null,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $variant = MenuVariant::create([
            'menu_id' => $menu->id,
            'name' => 'Regular',
            'price' => 10000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $ingredientA = Ingredient::create([
            'category_id' => $ingredientCategory->id,
            'name' => 'Daging',
            'display_unit' => 'gram',
            'base_unit' => 'gram',
            'stock' => 1000,
            'minimum_stock' => 100,
        ]);

        $ingredientB = Ingredient::create([
            'category_id' => $ingredientCategory->id,
            'name' => 'Saus',
            'display_unit' => 'ml',
            'base_unit' => 'ml',
            'stock' => 500,
            'minimum_stock' => 50,
        ]);

        return [$variant, $ingredientA, $ingredientB];
    }
}

