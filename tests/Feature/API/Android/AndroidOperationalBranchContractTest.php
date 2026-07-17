<?php

namespace Tests\Feature\API\Android;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DailyStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AndroidOperationalBranchContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cashier_reads_open_session_from_an_assigned_operational_branch(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $this->actingAs($admin)
            ->post(route('admin.daily-stocks.open'), [
                'date' => now('Asia/Jakarta')->toDateString(),
                'cashier_id' => $cashier->id,
            ])
            ->assertRedirect();
        $session = DailyStockSession::query()
            ->where('branch_id', $operationalBranch->id)
            ->where('cashier_id', $cashier->id)
            ->firstOrFail();
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sessions/current-status')
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.session_date', $session->session_date->toJSON());

        $this->assertDatabaseHas('daily_stock_sessions', [
            'id' => $session->id,
            'branch_id' => $operationalBranch->id,
            'cashier_id' => $cashier->id,
            'status' => 'open',
        ]);
    }

    public function test_current_status_and_daily_stock_items_use_the_same_assigned_branch_session(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $ingredient = $this->createIngredient('Daging Fixture', 'kg', 'g');
        $session = $this->openOperationalSession($admin, $cashier, $operationalBranch);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 1000,
            'remaining_qty' => 500,
            'used_qty' => 500,
            'returned_qty' => 0,
        ]);
        $token = $this->createApiTokenFor($cashier);

        $status = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sessions/current-status')
            ->assertOk();

        $stock = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/daily-stock-items')
            ->assertOk()
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.items.0.ingredient_id', $ingredient->id)
            ->assertJsonPath('data.items.0.qty', 1)
            ->assertJsonPath('data.items.0.remaining_qty', 0.5)
            ->assertJsonPath('data.items.0.unit', 'kg');

        $this->assertSame($status->json('data.session_id'), $stock->json('data.session_id'));
    }

    public function test_cashier_closes_the_same_assigned_branch_session_with_localized_decimal(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $ingredient = $this->createIngredient('Saus Fixture', 'l', 'ml');
        $session = $this->openOperationalSession($admin, $cashier, $operationalBranch);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 1000,
            'remaining_qty' => 1000,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/daily-stock-sessions/close', [
                'remaining' => [$ingredient->id => '0,25'],
            ])
            ->assertOk()
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('daily_stock_sessions', [
            'id' => $session->id,
            'branch_id' => $operationalBranch->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'remaining_qty' => 250,
        ]);
    }

    public function test_previous_date_open_session_is_not_treated_as_today_operational_session(): void
    {
        [$primaryBranch, , $admin, $cashier] = $this->createAssignedCashier();
        app(DailyStockService::class)->openSession(
            now('Asia/Jakarta')->subDay(),
            $cashier->id,
            $admin->id,
            null,
            $primaryBranch->id,
        );
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sessions/current-status')
            ->assertNotFound()
            ->assertJsonPath('active', false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/daily-stock-items')
            ->assertOk()
            ->assertJsonPath('data.session_id', null)
            ->assertJsonPath('data.items', []);
    }

    public function test_daily_stock_remaining_quantity_is_a_non_nullable_database_invariant(): void
    {
        $column = collect(Schema::getColumns('daily_stock_items'))
            ->firstWhere('name', 'remaining_qty');

        $this->assertNotNull($column);
        $this->assertFalse((bool) $column['nullable']);
        $default = trim((string) $column['default'], "'\"");
        $this->assertSame(0.0, (float) $default);
    }

    public function test_checkout_catalog_and_expense_use_the_assigned_operational_branch(): void
    {
        [$primaryBranch, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $ingredient = $this->createIngredient('Tortilla Fixture', 'pcs', 'pcs');
        $variant = $this->createSellableVariant($ingredient);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $session = $this->openOperationalSession($admin, $cashier, $operationalBranch);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('data.menus.0.variants.0.id', $variant->id)
            ->assertJsonPath('data.menus.0.variants.0.is_available', true);

        $checkout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'branch_id' => $primaryBranch->id,
                'payment_method_id' => $payment->id,
                'paid_amount' => 10000,
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $transactionId = (int) $checkout->json('data.transaction_id');
        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'branch_id' => $operationalBranch->id,
            'daily_stock_session_id' => $session->id,
            'user_id' => $cashier->id,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'id' => $transactionId,
            'branch_id' => $primaryBranch->id,
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'remaining_qty' => 9,
            'used_qty' => 1,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/cashflow/expenses', [
                'amount' => 5000,
                'source' => 'Operasional cabang',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('cashflow_entries', [
            'created_by' => $cashier->id,
            'branch_id' => $operationalBranch->id,
            'type' => 'expense',
            'amount' => 5000,
        ]);
        $this->assertSame(1, CashflowEntry::query()->count());
    }

    public function test_revenue_summary_uses_the_current_operational_branch_only(): void
    {
        [$primaryBranch, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $this->openOperationalSession($admin, $cashier, $operationalBranch);
        $this->createTransaction($cashier, $primaryBranch, $payment, 'TRX-A-SUMMARY');
        $operationalTransaction = $this->createTransaction(
            $cashier,
            $operationalBranch,
            $payment,
            'TRX-B-SUMMARY',
        );
        $operationalTransaction->update(['total_amount' => 25000, 'paid_amount' => 25000]);
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/revenue/summary?date='.now('Asia/Jakarta')->toDateString())
            ->assertOk()
            ->assertJsonPath('data.total_revenue', 25000)
            ->assertJsonPath('data.total_count', 1);
    }

    public function test_catalog_is_refreshed_when_checkout_consumes_the_last_daily_stock(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $ingredient = $this->createIngredient('Last Stock Fixture', 'pcs', 'pcs');
        $variant = $this->createSellableVariant($ingredient);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $session = $this->openOperationalSession($admin, $cashier, $operationalBranch);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        $token = $this->createApiTokenFor($cashier);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('data.menus.0.variants.0.id', $variant->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 10000,
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            ])
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonCount(0, 'data.menus');
    }

    public function test_cashier_history_and_receipt_keep_owned_transactions_across_assigned_branches(): void
    {
        [$primaryBranch, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $this->openOperationalSession($admin, $cashier, $operationalBranch);
        $primaryTransaction = $this->createTransaction($cashier, $primaryBranch, $payment, 'TRX-A-001');
        $operationalTransaction = $this->createTransaction($cashier, $operationalBranch, $payment, 'TRX-B-001');
        $token = $this->createApiTokenFor($cashier);

        $history = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/transactions?per_page=15')
            ->assertOk();

        $this->assertCount(2, $history->json('data.data'));
        $this->assertEqualsCanonicalizing(
            [$primaryTransaction->transaction_code, $operationalTransaction->transaction_code],
            collect($history->json('data.data'))->pluck('transaction_code')->all(),
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/transactions/'.$operationalTransaction->transaction_code.'/receipt')
            ->assertOk()
            ->assertJsonPath('data.transaction_code', $operationalTransaction->transaction_code);
    }

    public function test_cashier_voids_an_owned_transaction_on_the_assigned_operational_session(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $ingredient = $this->createIngredient('Void Fixture', 'pcs', 'pcs');
        $variant = $this->createSellableVariant($ingredient);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $session = $this->openOperationalSession($admin, $cashier, $operationalBranch);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        $token = $this->createApiTokenFor($cashier);

        $checkout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 10000,
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            ])
            ->assertCreated();
        $transactionId = (int) $checkout->json('data.transaction_id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonCount(0, 'data.menus');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Idempotency-Key' => 'void-operational-branch-fixture',
        ])->postJson('/api/transactions/'.$transactionId.'/void', [
            'current_session_id' => $session->id,
            'reason' => 'restock',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'branch_id' => $operationalBranch->id,
            'status' => 'VOID',
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'remaining_qty' => 1,
            'used_qty' => 0,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('data.menus.0.variants.0.id', $variant->id);
    }

    public function test_cashier_cannot_void_a_transaction_from_a_previous_operational_session(): void
    {
        [, $operationalBranch, $admin, $cashier] = $this->createAssignedCashier();
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $oldSession = app(DailyStockService::class)->openSession(
            now('Asia/Jakarta')->subDay(),
            $cashier->id,
            $admin->id,
            null,
            $operationalBranch->id,
        );
        $transaction = $this->createTransaction($cashier, $operationalBranch, $payment, 'TRX-B-OLD');
        $transaction->update(['daily_stock_session_id' => $oldSession->id]);
        $token = $this->createApiTokenFor($cashier);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Idempotency-Key' => 'void-previous-operational-session',
        ])->postJson('/api/transactions/'.$transaction->id.'/void', [
            'current_session_id' => $oldSession->id,
            'reason' => 'restock',
        ])->assertForbidden();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'SUCCESS',
            'voided_at' => null,
        ]);
        $this->assertDatabaseMissing('cashflow_entries', [
            'source' => 'Transaction Void',
        ]);
    }

    /**
     * @return array{Branch, Branch, User, User}
     */
    private function createAssignedCashier(): array
    {
        $primaryBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $operationalBranch = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->create(['name' => 'admin']);
        $cashierRole = Role::query()->create(['name' => 'kasir']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => $operationalBranch->id,
        ]);
        $cashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $primaryBranch->id,
        ]);
        $cashier->assignedBranches()->attach($operationalBranch->id);

        return [$primaryBranch, $operationalBranch, $admin, $cashier];
    }

    private function openOperationalSession(User $admin, User $cashier, Branch $branch): DailyStockSession
    {
        return app(DailyStockService::class)->openSession(
            now('Asia/Jakarta'),
            $cashier->id,
            $admin->id,
            'Sesi dibuka admin pada cabang operasional.',
            $branch->id,
        );
    }

    private function createIngredient(string $name, string $displayUnit, string $baseUnit): Ingredient
    {
        return Ingredient::query()->create([
            'name' => $name,
            'display_unit' => $displayUnit,
            'base_unit' => $baseUnit,
            'pack_size' => 1,
            'stock' => 10000,
            'minimum_stock' => 0,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
    }

    private function createSellableVariant(Ingredient $ingredient): MenuVariant
    {
        $menu = Menu::query()->create([
            'name' => 'Kebab Operational Fixture',
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
        DB::table('menu_variant_ingredients')->insert([
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $variant;
    }

    private function createTransaction(
        User $cashier,
        Branch $branch,
        PaymentMethod $payment,
        string $code,
    ): Transaction {
        return Transaction::query()->create([
            'transaction_code' => $code,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'total_amount' => 10000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
    }

    private function createApiTokenFor(User $user): string
    {
        $token = 'operational_branch_'.bin2hex(random_bytes(8));

        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'android-operational-branch-test',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        return $token;
    }
}
