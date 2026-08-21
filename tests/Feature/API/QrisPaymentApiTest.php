<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\QrisConfig;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\QrisTestPayload;
use Tests\TestCase;

class QrisPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_methods_keep_cash_and_add_qris_on_a_fresh_install(): void
    {
        [, , $token] = $this->cashierContext('METHOD');

        $response = $this->withToken($token)->getJson('/api/payment-methods')->assertOk();

        $names = collect($response->json('data.payment_methods'))->pluck('name');
        $this->assertTrue($names->contains('Cash'));
        $this->assertTrue($names->contains('QRIS'));
    }

    public function test_qris_is_generated_from_transaction_amount_and_its_own_branch_config(): void
    {
        [$branchA, $cashierA, $tokenA] = $this->cashierContext('A');
        [$branchB, $cashierB, $tokenB] = $this->cashierContext('B');
        $configA = $this->config($branchA, $cashierA, 'MERCHANT CABANG A', 'AMERCHANT00001');
        $configA->update(['merchant_display_name' => 'MERCHANT CABANG A LENGKAP']);
        $this->config($branchB, $cashierB, 'MERCHANT CABANG B', 'BMERCHANT00001');
        $transactionA = $this->transaction($branchA, $cashierA, '25000.00', 'A');
        $transactionB = $this->transaction($branchB, $cashierB, '30000.00', 'B');

        $responseA = $this->withToken($tokenA)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transactionA->id,
            'amount' => 1,
            'branch_id' => $branchB->id,
        ])->assertOk()
            ->assertJsonPath('data.branch_id', $branchA->id)
            ->assertJsonPath('data.merchant_name', 'MERCHANT CABANG A LENGKAP')
            ->assertJsonPath('data.amount', 25000);

        $responseB = $this->withToken($tokenB)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transactionB->id,
        ])->assertOk()
            ->assertJsonPath('data.branch_id', $branchB->id)
            ->assertJsonPath('data.merchant_name', 'MERCHANT CABANG B')
            ->assertJsonPath('data.amount', 30000);

        $this->assertStringContainsString('540525000', $responseA->json('data.qris_payload'));
        $this->assertStringContainsString('540530000', $responseB->json('data.qris_payload'));
        $this->assertStringNotContainsString('MERCHANT CABANG B', $responseA->json('data.qris_payload'));
        $this->assertStringContainsString('MERCHANT CABANG A', $responseA->json('data.qris_payload'));
    }

    public function test_cashier_cannot_generate_qris_for_another_branch_transaction(): void
    {
        [, , $tokenA] = $this->cashierContext('A');
        [$branchB, $cashierB] = $this->cashierContext('B');
        $this->config($branchB, $cashierB, 'MERCHANT B', 'BMERCHANT00002');
        $transactionB = $this->transaction($branchB, $cashierB, '30000.00', 'B');

        $this->withToken($tokenA)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transactionB->id,
        ])->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_missing_config_wrong_payment_method_and_missing_transaction_return_safe_errors(): void
    {
        [$branch, $cashier, $token] = $this->cashierContext('ERR');
        $qrisTransaction = $this->transaction($branch, $cashier, '10000.00', 'ERR');

        $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $qrisTransaction->id,
        ])->assertNotFound()->assertJsonPath('message', 'QRIS belum dikonfigurasi untuk cabang ini.');

        $cash = PaymentMethod::query()->firstOrCreate(['name' => 'Cash']);
        $qrisTransaction->update(['payment_method_id' => $cash->id]);
        $this->config($branch, $cashier, 'MERCHANT ERROR', 'ERRMERCHANT001');

        $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $qrisTransaction->id,
        ])->assertStatus(409)
            ->assertJsonPath('message', 'Transaksi ini tidak menggunakan metode pembayaran QRIS.');

        $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => 999999,
        ])->assertNotFound()->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    /** @return array{Branch,User,string} */
    private function cashierContext(string $suffix): array
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang '.$suffix,
            'code' => 'API-'.$suffix,
            'is_active' => true,
        ]);
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $plainToken = 'qris_'.bin2hex(random_bytes(12));
        ApiToken::query()->create([
            'user_id' => $cashier->id,
            'name' => 'qris-test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return [$branch, $cashier, $plainToken];
    }

    private function config(Branch $branch, User $actor, string $merchant, string $merchantId): QrisConfig
    {
        return QrisConfig::query()->create([
            'branch_id' => $branch->id,
            'merchant_name' => $merchant,
            'merchant_city' => 'KUDUS',
            'qris_payload' => QrisTestPayload::make($merchant, 'KUDUS', $merchantId),
            'is_active' => true,
            'activated_at' => now(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function transaction(Branch $branch, User $cashier, string $amount, string $suffix): Transaction
    {
        $payment = PaymentMethod::query()->firstOrCreate(['name' => 'QRIS']);

        return Transaction::query()->create([
            'transaction_code' => 'TRX-QRIS-'.$suffix.'-'.uniqid(),
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'total_amount' => $amount,
            'payment_method_id' => $payment->id,
            'paid_amount' => $amount,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
    }
}
