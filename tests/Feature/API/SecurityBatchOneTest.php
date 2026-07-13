<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PasswordOtp;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityBatchOneTest extends TestCase
{
    use RefreshDatabase;

    private ?Branch $branch = null;

    public function test_checkout_uses_database_variant_price_and_ignores_client_price(): void
    {
        [$user, $token] = $this->createUserWithToken('kasir');
        $variant = $this->createSellableVariant(price: 25000, requiredQty: 1);
        $session = $this->openSession($user->id);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => (int) DB::table('menu_variant_ingredients')
                ->where('menu_variant_id', $variant->id)
                ->value('ingredient_id'),
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 100000,
                'items' => [
                    [
                        'variant_id' => $variant->id,
                        'qty' => 2,
                        'price' => 1,
                    ],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.total_amount', 50000);
        $response->assertJsonPath('data.items.0.price', 25000);

        $this->assertDatabaseHas('transaction_details', [
            'menu_variant_id' => $variant->id,
            'price' => 25000,
            'subtotal' => 50000,
        ]);
    }

    public function test_checkout_keeps_its_response_contract_and_rebuilds_branch_summary(): void
    {
        [$user, $token] = $this->createUserWithToken('kasir');
        $variant = $this->createSellableVariant(price: 12500, requiredQty: 1);
        $session = $this->openSession($user->id);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $ingredientId = (int) DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->value('ingredient_id');

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredientId,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 30000,
                'items' => [
                    ['variant_id' => $variant->id, 'qty' => 2],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Transaksi berhasil')
            ->assertJsonPath('data.payment_method.id', $payment->id)
            ->assertJsonPath('data.items.0.variant_id', $variant->id)
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.total_amount', 25000)
            ->assertJsonPath('data.paid_amount', 30000)
            ->assertJsonPath('data.change_amount', 5000);

        $transactionId = (int) $response->json('data.transaction_id');

        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'branch_id' => $this->testBranch()->id,
            'user_id' => $user->id,
            'status' => 'SUCCESS',
            'daily_stock_session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $transactionId,
            'menu_variant_id' => $variant->id,
            'quantity' => 2,
            'subtotal' => 25000,
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'branch_id' => $this->testBranch()->id,
            'ingredient_id' => $ingredientId,
            'type' => 'daily_usage',
            'reference_id' => $transactionId,
            'quantity' => -2,
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredientId,
            'remaining_qty' => 8,
            'used_qty' => 2,
        ]);
        $this->assertDatabaseHas('daily_sales_summaries', [
            'branch_id' => $this->testBranch()->id,
            'sale_date' => now('Asia/Jakarta')->toDateString(),
            'total_transactions' => 1,
            'total_revenue' => 25000,
            'total_items_sold' => 2,
        ]);
    }

    public function test_checkout_rolls_back_when_only_another_branch_has_usable_stock(): void
    {
        [$user, $token] = $this->createUserWithToken('kasir');
        $variant = $this->createSellableVariant(price: 10000, requiredQty: 1);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $ingredientId = (int) DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->value('ingredient_id');
        $otherBranch = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $otherBranchSession = DailyStockSession::query()->create([
            'session_date' => now('Asia/Jakarta')->toDateString(),
            'cashier_id' => $user->id,
            'opened_by' => $user->id,
            'branch_id' => $otherBranch->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $otherBranchSession->id,
            'ingredient_id' => $ingredientId,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $payment->id,
                'paid_amount' => 10000,
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Transaksi gagal diproses. Periksa stok harian dan data transaksi lalu coba lagi.');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_details', 0);
        $this->assertDatabaseCount('stock_logs', 0);
        $this->assertDatabaseCount('daily_sales_summaries', 0);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $otherBranchSession->id,
            'ingredient_id' => $ingredientId,
            'remaining_qty' => 10,
            'used_qty' => 0,
        ]);
    }

    public function test_write_api_rejects_non_kasir_role_with_consistent_forbidden_response(): void
    {
        [, $token] = $this->createUserWithToken('admin');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', []);

        $response->assertForbidden();
        $response->assertExactJson([
            'success' => false,
            'message' => 'Akses tidak diizinkan.',
        ]);
    }

    public function test_cashier_expense_entry_date_is_forced_to_server_today(): void
    {
        [$user, $token] = $this->createUserWithToken('kasir');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cashflow/expenses', [
                'amount' => 15000,
                'source' => 'Operasional',
                'entry_date' => now()->subDays(10)->toDateString(),
                'note' => 'test',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.entry_date', now()->toDateString());

        $entry = CashflowEntry::query()->firstOrFail();
        $this->assertSame($user->id, $entry->created_by);
        $this->assertSame(now()->toDateString(), $entry->entry_date->toDateString());
    }

    public function test_forgot_password_does_not_enumerate_registered_email(): void
    {
        Mail::fake();
        $role = Role::query()->create(['name' => 'kasir']);
        User::factory()->create([
            'role_id' => $role->id,
            'email' => 'registered@example.test',
        ]);

        $registered = $this->postJson('/api/auth/forgot-password', [
            'email' => 'registered@example.test',
        ]);

        $missing = $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.test',
        ]);

        $registered->assertOk();
        $missing->assertOk();
        $this->assertTrue($registered->json('success'));
        $this->assertTrue($missing->json('success'));
        $this->assertSame(
            'Jika email terdaftar, kode reset akan dikirim.',
            $registered->json('message')
        );
        $this->assertSame($registered->json('message'), $missing->json('message'));
    }

    public function test_otp_attempt_limit_invalidates_code_for_verify_and_reset(): void
    {
        [$user] = $this->createUserWithToken('kasir');
        $user->update(['password' => Hash::make('old-password')]);

        $verifyOtp = PasswordOtp::query()->create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 5,
            'used' => false,
        ]);

        $verifyResponse = $this->postJson('/api/auth/verify-reset-code', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $verifyResponse->assertStatus(422);
        $verifyResponse->assertJsonPath('message', 'Kode OTP tidak valid atau sudah tidak berlaku.');
        $this->assertTrue($verifyOtp->fresh()->used);

        $resetOtp = PasswordOtp::query()->create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 5,
            'used' => false,
        ]);

        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'code' => '654321',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $resetResponse->assertStatus(422);
        $resetResponse->assertJsonPath('message', 'Kode OTP tidak valid atau sudah tidak berlaku.');
        $this->assertTrue($resetOtp->fresh()->used);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_successful_password_reset_revokes_api_tokens(): void
    {
        [$user] = $this->createUserWithToken('kasir');

        PasswordOtp::query()->create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('api_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array{User,string}
     */
    private function createUserWithToken(string $roleName): array
    {
        $role = Role::query()->create(['name' => $roleName]);
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

    private function createSellableVariant(float $price, float $requiredQty): MenuVariant
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
            'price' => $price,
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
