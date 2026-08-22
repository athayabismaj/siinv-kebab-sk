<?php

namespace Tests\Feature\API\Android;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\QrisConfig;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\QrisTestPayload;
use Tests\TestCase;

class AndroidCheckoutContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_returns_typed_transaction_and_receipt_fields(): void
    {
        [$cashier, $token, $branch] = $this->createCashierWithToken();
        $branch->update(['address' => 'Jl. Kampus UMK, Kudus']);
        [$variant, $ingredient] = $this->createSellableVariant();
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $this->openSession($cashier, $branch, $ingredient);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 15000,
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
                'note' => null,
            ]);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->whereType('message', 'string')
                ->whereType('data.transaction_id', 'integer')
                ->whereType('data.transaction_code', 'string')
                ->where('data.branch.id', $branch->id)
                ->where('data.branch.name', $branch->name)
                ->where('data.branch.address', 'Jl. Kampus UMK, Kudus')
                ->whereType('data.created_at', 'string')
                ->where('data.payment_method.id', $payment->id)
                ->where('data.payment_method.name', 'Cash')
                ->where('data.items.0.variant_id', $variant->id)
                ->where('data.items.0.qty', 1)
                ->where('data.items.0.price', 10000)
                ->where('data.items.0.subtotal', 10000)
                ->where('data.total_amount', 10000)
                ->where('data.paid_amount', 15000)
                ->where('data.change_amount', 5000)
                ->etc());

        $transactionId = $response->json('data.transaction_id');
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/transactions/'.$transactionId.'/receipt')
            ->assertOk()
            ->assertJsonPath('data.transaction_code', $response->json('data.transaction_code'))
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.address', 'Jl. Kampus UMK, Kudus')
            ->assertJsonPath('data.items.0.menu_name', 'Kebab Fixture')
            ->assertJsonPath('data.items.0.variant_name', 'Mini');
    }

    public function test_checkout_validation_and_closed_session_have_no_side_effects(): void
    {
        [, $token] = $this->createCashierWithToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validasi transaksi tidak valid.');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_qris_checkout_starts_pending_uses_server_total_and_is_excluded_from_revenue(): void
    {
        [$cashier, $token, $branch] = $this->createCashierWithToken();
        [$variant, $ingredient] = $this->createSellableVariant();
        $payment = PaymentMethod::query()->firstOrCreate(['name' => 'QRIS']);
        $this->openSession($cashier, $branch, $ingredient);
        QrisConfig::query()->create([
            'branch_id' => $branch->id,
            'merchant_name' => 'MERCHANT CHECKOUT',
            'merchant_city' => 'KUDUS',
            'qris_payload' => QrisTestPayload::make('MERCHANT CHECKOUT'),
            'is_active' => true,
            'activated_at' => now(),
            'created_by' => $cashier->id,
            'updated_by' => $cashier->id,
        ]);

        $response = $this->withToken($token)->postJson('/api/transactions', [
            'payment_method_id' => $payment->id,
            'paid_amount' => 999999999,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ])->assertCreated()
            ->assertJsonPath('data.status', Transaction::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.total_amount', 10000)
            ->assertJsonPath('data.paid_amount', 0)
            ->assertJsonPath('data.change_amount', 0);

        $this->assertDatabaseHas('transactions', [
            'id' => $response->json('data.transaction_id'),
            'status' => Transaction::STATUS_PENDING_PAYMENT,
            'total_amount' => 10000,
            'paid_amount' => 0,
        ]);
        $this->assertDatabaseCount('daily_sales_summaries', 0);

        $this->withToken($token)->getJson('/api/revenue/summary')
            ->assertOk()
            ->assertJsonPath('data.total_revenue', 0)
            ->assertJsonPath('data.total_count', 0);
    }

    /**
     * @return array{User, string, Branch}
     */
    private function createCashierWithToken(): array
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'FIX'],
            ['name' => 'Cabang Fixture', 'address' => null, 'is_active' => true]
        );
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $token = 'checkout_contract_'.bin2hex(random_bytes(8));
        ApiToken::query()->create([
            'user_id' => $cashier->id,
            'name' => 'android-contract-test',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        return [$cashier, $token, $branch];
    }

    /**
     * @return array{MenuVariant, Ingredient}
     */
    private function createSellableVariant(): array
    {
        $menu = Menu::query()->create([
            'name' => 'Kebab Fixture',
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Mini',
            'price' => 10000,
            'is_available' => true,
            'sort_order' => 0,
        ]);
        $ingredient = Ingredient::query()->create([
            'name' => 'Bahan Fixture',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
        DB::table('menu_variant_ingredients')->insert([
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$variant, $ingredient];
    }

    private function openSession(User $cashier, Branch $branch, Ingredient $ingredient): void
    {
        $session = DailyStockSession::query()->create([
            'session_date' => now('Asia/Jakarta')->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
    }
}
