<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\QrisConfig;
use App\Models\QrisPaymentAttempt;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Owner\TransactionHistoryQueryService;
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
            ->assertJsonPath('data.amount', 25000)
            ->assertJsonStructure(['data' => ['qris_reference', 'generated_at', 'expires_at']]);

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

    public function test_pending_payment_becomes_success_with_manual_audit_and_enters_revenue_once(): void
    {
        [$branch, $cashier, $token] = $this->cashierContext('CONFIRM');
        $this->config($branch, $cashier, 'MERCHANT CONFIRM', 'CONFIRM000001');
        $transaction = $this->transaction($branch, $cashier, '25000.00', 'CONFIRM');

        $this->withToken($token)->getJson('/api/revenue/summary')
            ->assertOk()->assertJsonPath('data.total_revenue', 0)->assertJsonPath('data.total_count', 0);
        $pendingReport = app(TransactionHistoryQueryService::class)->summary(
            now()->subDay(),
            now()->addDay(),
            ['branch_id' => $branch->id],
        );
        $this->assertSame(0, $pendingReport['total_transactions']);
        $this->assertSame(0.0, $pendingReport['total_revenue']);

        $generated = $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transaction->id,
            'amount' => 1,
        ])->assertOk();

        $reference = (string) $generated->json('data.qris_reference');
        $this->withToken($token)->postJson('/api/payments/qris/confirm', [
            'transaction_id' => $transaction->id,
            'qris_reference' => $reference,
        ])->assertOk()
            ->assertJsonPath('data.status', Transaction::STATUS_SUCCESS)
            ->assertJsonPath('data.confirmed_by.id', $cashier->id)
            ->assertJsonPath('data.confirmation_source', 'manual_cashier');

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame($cashier->id, (int) $transaction->payment_confirmed_by);
        $this->assertNotNull($transaction->payment_confirmed_at);
        $this->assertSame(25000.0, (float) $transaction->getRawOriginal('paid_amount'));

        $this->withToken($token)->getJson('/api/revenue/summary')
            ->assertOk()->assertJsonPath('data.total_revenue', 25000)->assertJsonPath('data.total_count', 1);
        $confirmedReport = app(TransactionHistoryQueryService::class)->summary(
            now()->subDay(),
            now()->addDay(),
            ['branch_id' => $branch->id],
        );
        $this->assertSame(1, $confirmedReport['total_transactions']);
        $this->assertSame(25000.0, $confirmedReport['total_revenue']);

        $this->withToken($token)->postJson('/api/payments/qris/confirm', [
            'transaction_id' => $transaction->id,
            'qris_reference' => $reference,
        ])->assertStatus(409)->assertJsonPath('message', 'Pembayaran QRIS sudah pernah dikonfirmasi.');

        $this->withToken($token)->getJson('/api/revenue/summary')
            ->assertOk()->assertJsonPath('data.total_revenue', 25000)->assertJsonPath('data.total_count', 1);
    }

    public function test_expired_qr_is_rejected_and_a_new_reference_replaces_it(): void
    {
        [$branch, $cashier, $token] = $this->cashierContext('EXPIRED');
        $this->config($branch, $cashier, 'MERCHANT EXPIRED', 'EXPIRED000001');
        $transaction = $this->transaction($branch, $cashier, '12000.00', 'EXPIRED');

        $first = $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transaction->id,
        ])->assertOk();
        $firstReference = (string) $first->json('data.qris_reference');
        $firstPayload = (string) $first->json('data.qris_payload');
        QrisPaymentAttempt::query()->where('reference', $firstReference)->update(['expires_at' => now()->subSecond()]);

        $this->withToken($token)->postJson('/api/payments/qris/confirm', [
            'transaction_id' => $transaction->id,
            'qris_reference' => $firstReference,
        ])->assertStatus(410)
            ->assertJsonPath('message', 'QRIS sudah kedaluwarsa. Buat QRIS baru untuk melanjutkan.');

        $this->assertSame(Transaction::STATUS_PENDING_PAYMENT, $transaction->fresh()->status);
        $this->assertDatabaseHas('qris_payment_attempts', [
            'reference' => $firstReference,
            'status' => QrisPaymentAttempt::STATUS_EXPIRED,
        ]);

        $second = $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $transaction->id,
        ])->assertOk();
        $this->assertNotSame($firstReference, (string) $second->json('data.qris_reference'));
        $this->assertNotSame($firstPayload, (string) $second->json('data.qris_payload'));
    }

    public function test_reference_cannot_be_reused_for_another_transaction_or_by_another_cashier(): void
    {
        [$branch, $cashier, $token] = $this->cashierContext('REUSE');
        [, , $foreignToken] = $this->cashierContext('FOREIGN');
        $this->config($branch, $cashier, 'MERCHANT REUSE', 'REUSE0000001');
        $first = $this->transaction($branch, $cashier, '10000.00', 'R1');
        $second = $this->transaction($branch, $cashier, '10000.00', 'R2');

        $generated = $this->withToken($token)->postJson('/api/payments/qris/generate', [
            'transaction_id' => $first->id,
        ])->assertOk();
        $reference = (string) $generated->json('data.qris_reference');

        $this->withToken($token)->postJson('/api/payments/qris/confirm', [
            'transaction_id' => $second->id,
            'qris_reference' => $reference,
        ])->assertStatus(409)
            ->assertJsonPath('message', 'Referensi QRIS sudah tidak aktif atau telah digunakan.');

        $this->withToken($foreignToken)->postJson('/api/payments/qris/confirm', [
            'transaction_id' => $first->id,
            'qris_reference' => $reference,
        ])->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses untuk mengonfirmasi transaksi ini.');

        $this->assertSame(Transaction::STATUS_PENDING_PAYMENT, $first->fresh()->status);
        $this->assertSame(Transaction::STATUS_PENDING_PAYMENT, $second->fresh()->status);
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
            'paid_amount' => 0,
            'change_amount' => 0,
            'status' => Transaction::STATUS_PENDING_PAYMENT,
        ]);
    }
}
