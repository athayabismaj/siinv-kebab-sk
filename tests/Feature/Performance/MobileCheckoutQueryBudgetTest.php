<?php

namespace Tests\Feature\Performance;

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

class MobileCheckoutQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_query_growth_stays_flat_for_a_larger_cart(): void
    {
        [$cashier, $token, $branch] = $this->cashierWithToken();
        $ingredients = collect(range(1, 3))->map(fn (int $number) => Ingredient::query()->create([
            'name' => 'Bahan Checkout '.$number,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
        ]));
        $menu = Menu::query()->create([
            'name' => 'Menu Checkout Batch',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $variants = collect(range(1, 8))->map(function (int $number) use ($menu, $ingredients) {
            $variant = MenuVariant::query()->create([
                'menu_id' => $menu->id,
                'name' => 'Varian '.$number,
                'price' => 10000,
                'is_available' => true,
                'sort_order' => $number,
            ]);

            DB::table('menu_variant_ingredients')->insert($ingredients->map(fn (Ingredient $ingredient) => [
                'menu_variant_id' => $variant->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());

            return $variant;
        });
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $session = DailyStockSession::query()->create([
            'session_date' => now('Asia/Jakarta')->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        DailyStockItem::query()->insert($ingredients->map(fn (Ingredient $ingredient) => [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 100,
            'remaining_qty' => 100,
            'used_qty' => 0,
            'returned_qty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $smallCount = $this->checkoutQueryCount($token, $payment->id, [
            ['variant_id' => $variants->first()->id, 'qty' => 1],
        ]);
        $largeCount = $this->checkoutQueryCount(
            $token,
            $payment->id,
            $variants->map(fn (MenuVariant $variant) => [
                'variant_id' => $variant->id,
                'qty' => 1,
            ])->all(),
        );

        $this->assertLessThanOrEqual(
            $smallCount + 1,
            $largeCount,
            "Checkout query growth is not flat: small={$smallCount}, large={$largeCount}",
        );
        $this->assertLessThanOrEqual(12, $largeCount, "Checkout query budget exceeded: {$largeCount}");
    }

    /**
     * @param  array<int, array{variant_id:int,qty:int}>  $items
     */
    private function checkoutQueryCount(string $token, int $paymentMethodId, array $items): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $paymentMethodId,
                'paid_amount' => 200000,
                'items' => $items,
            ])
            ->assertCreated();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * @return array{User,string,Branch}
     */
    private function cashierWithToken(): array
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang Checkout Performance',
            'code' => 'checkout-performance',
            'is_active' => true,
        ]);
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $token = 'checkout_performance_'.bin2hex(random_bytes(8));
        ApiToken::query()->create([
            'user_id' => $cashier->id,
            'name' => 'checkout-performance-test',
            'token_hash' => hash('sha256', $token),
            'last_used_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return [$cashier, $token, $branch];
    }
}
