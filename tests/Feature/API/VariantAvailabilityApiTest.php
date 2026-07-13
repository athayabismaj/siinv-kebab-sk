<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariantAvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    private ?Branch $branch = null;

    public function test_menu_endpoint_returns_no_session_reason_when_session_not_open(): void
    {
        [$user, $token] = $this->createKasirWithToken();
        $variant = $this->createVariantWithRecipe();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus');

        $response->assertOk();
        $menus = data_get($response->json(), 'data.menus', []);
        $this->assertCount(0, $menus, 'Kasir tidak boleh menerima varian unavailable pada list jual.');

        $diagnostics = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus/unavailable-variants');
        $diagnostics->assertForbidden();

        // owner/admin diagnostics check via forced role update
        $adminRole = Role::query()->create(['name' => 'admin']);
        $user->update(['role_id' => $adminRole->id]);

        $adminDiagnostics = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus/unavailable-variants?cashier_id=' . $user->id);
        $adminDiagnostics->assertOk();
        $row = collect(data_get($adminDiagnostics->json(), 'data.rows', []))
            ->firstWhere('variant_id', $variant->id);

        $this->assertNotNull($row);
        $this->assertSame('NO_SESSION', data_get($row, 'unavailable_reason'));
    }

    public function test_menu_endpoint_marks_no_recipe_and_stock_reasons(): void
    {
        [$user, $token] = $this->createKasirWithToken();
        $session = $this->openSession($user->id);

        $noRecipeVariant = $this->createVariantWithoutRecipe();
        $withRecipeVariant = $this->createVariantWithRecipe();

        // recipe exists but ingredient not transferred yet
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus');
        $response->assertOk();
        $this->assertCount(0, data_get($response->json(), 'data.menus', []));

        $ownerRole = Role::query()->create(['name' => 'owner']);
        $user->update(['role_id' => $ownerRole->id]);
        $diagnostics = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus/unavailable-variants?cashier_id=' . $user->id);
        $diagnostics->assertOk();

        $rows = collect(data_get($diagnostics->json(), 'data.rows', []));
        $this->assertSame('NO_RECIPE', data_get($rows->firstWhere('variant_id', $noRecipeVariant->id), 'unavailable_reason'));
        $this->assertSame('INGREDIENT_NOT_TRANSFERRED', data_get($rows->firstWhere('variant_id', $withRecipeVariant->id), 'unavailable_reason'));

        // transfer but insufficient
        $ingredientId = DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $withRecipeVariant->id)
            ->value('ingredient_id');

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => (int) $ingredientId,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $diagnostics2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/menus/unavailable-variants?cashier_id=' . $user->id);
        $diagnostics2->assertOk();
        $rows2 = collect(data_get($diagnostics2->json(), 'data.rows', []));
        $this->assertSame('INSUFFICIENT_STOCK', data_get($rows2->firstWhere('variant_id', $withRecipeVariant->id), 'unavailable_reason'));
    }

    public function test_checkout_revalidates_stock_and_rejects_when_insufficient(): void
    {
        [$user, $token] = $this->createKasirWithToken();
        $variant = $this->createVariantWithRecipe(requiredQty: 2);
        $session = $this->openSession($user->id);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        $ingredientId = DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->value('ingredient_id');

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => (int) $ingredientId,
            'opening_qty' => 3,
            'remaining_qty' => 3,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $payload = [
            'payment_method_id' => $payment->id,
            'paid_amount' => 100000,
            'items' => [
                ['variant_id' => $variant->id, 'qty' => 2], // needs 4, available 3
            ],
            'note' => 'test',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('data.unavailable_reason', 'INSUFFICIENT_STOCK');
    }

    public function test_sequential_checkout_second_request_fails_when_stock_runs_out(): void
    {
        [$user, $token] = $this->createKasirWithToken();
        $variant = $this->createVariantWithRecipe(requiredQty: 1);
        $session = $this->openSession($user->id);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        $ingredientId = DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->value('ingredient_id');

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => (int) $ingredientId,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $payload = [
            'payment_method_id' => $payment->id,
            'paid_amount' => 100000,
            'items' => [
                ['variant_id' => $variant->id, 'qty' => 1],
            ],
        ];

        $first = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', $payload);
        $first->assertCreated();

        $second = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', $payload);
        $second->assertStatus(422);
        $second->assertJsonPath('data.unavailable_reason', 'INSUFFICIENT_STOCK');
    }

    /**
     * @return array{User,string}
     */
    private function createKasirWithToken(): array
    {
        $role = Role::query()->create(['name' => 'kasir']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $this->testBranch()->id,
        ]);

        $plainToken = 'tok_' . bin2hex(random_bytes(12));
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'test-token',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return [$user, $plainToken];
    }

    private function createVariantWithoutRecipe(): MenuVariant
    {
        $menu = Menu::query()->create([
            'name' => 'Menu No Recipe ' . uniqid(),
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Regular',
            'price' => 25000,
            'is_available' => true,
            'sort_order' => 0,
        ]);
    }

    private function createVariantWithRecipe(float $requiredQty = 2): MenuVariant
    {
        $menu = Menu::query()->create([
            'name' => 'Menu Recipe ' . uniqid(),
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Regular',
            'price' => 25000,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $ingredient = Ingredient::query()->create([
            'name' => 'Bahan ' . uniqid(),
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
            'selling_price' => 1000,
        ]);

        DB::table('menu_variant_ingredients')->insert([
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $requiredQty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $variant;
    }

    private function openSession(int $cashierId): DailyStockSession
    {
        return DailyStockSession::query()->create([
            'session_date' => now('Asia/Jakarta')->toDateString(),
            'cashier_id' => $cashierId,
            'opened_by' => $cashierId,
            'branch_id' => $this->testBranch()->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    private function testBranch(): Branch
    {
        return $this->branch ??= Branch::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Kebab SK', 'is_active' => true],
        );
    }
}
