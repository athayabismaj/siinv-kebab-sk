<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionReceiptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_fetch_transaction_detail_with_receipt_items_by_id(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$transaction] = $this->createTransactionWithDetail($cashier);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions/' . $transaction->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.transaction_code', $transaction->transaction_code)
            ->assertJsonPath('data.payment_method_name', 'Cash')
            ->assertJsonPath('data.total_amount', 2500)
            ->assertJsonPath('data.items.0.menu_name', 'Kebab Mini')
            ->assertJsonPath('data.items.0.variant_name', 'Mini')
            ->assertJsonPath('data.items.0.qty', 1)
            ->assertJsonPath('data.items.0.price', 2500)
            ->assertJsonPath('data.items.0.subtotal', 2500);
    }

    public function test_cashier_can_fetch_transaction_receipt_by_transaction_code(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$transaction] = $this->createTransactionWithDetail($cashier);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions/' . $transaction->transaction_code . '/receipt');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_code', $transaction->transaction_code)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_cashier_cannot_fetch_another_cashiers_transaction_detail(): void
    {
        [, $token] = $this->createUserWithToken('kasir');
        [$otherCashier] = $this->createUserWithToken('kasir');
        [$transaction] = $this->createTransactionWithDetail($otherCashier);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions/' . $transaction->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    /**
     * @return array{User,string}
     */
    private function createUserWithToken(string $roleName): array
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create([
            'role_id' => $role->id,
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

    /**
     * @return array{Transaction,MenuVariant}
     */
    private function createTransactionWithDetail(User $cashier): array
    {
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);
        $menu = Menu::query()->create([
            'name' => 'Kebab Mini',
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Mini',
            'price' => 2500,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-20260705-214326-UNQE',
            'user_id' => $cashier->id,
            'total_amount' => 2500,
            'payment_method_id' => $payment->id,
            'paid_amount' => 2500,
            'change_amount' => 0,
            'status' => 'VOID',
        ]);

        TransactionDetail::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 2500,
            'subtotal' => 2500,
        ]);

        return [$transaction, $variant];
    }
}
