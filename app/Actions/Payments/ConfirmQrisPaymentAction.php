<?php

namespace App\Actions\Payments;

use App\Models\QrisPaymentAttempt;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Analytics\DailySalesSummaryService;
use App\Support\AdminCache;
use App\Support\BranchScope;
use Illuminate\Support\Facades\DB;

class ConfirmQrisPaymentAction
{
    public const SOURCE_MANUAL = 'manual_cashier';

    public const SOURCE_PROVIDER = 'provider_inquiry';

    public function __construct(private readonly DailySalesSummaryService $dailySalesSummaryService) {}

    /** @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>} */
    public function executeManual(int $transactionId, string $reference, User $actor): array
    {
        return $this->confirm($transactionId, $reference, $actor, self::SOURCE_MANUAL);
    }

    /**
     * Shared state machine. A future signed bank inquiry/webhook adapter can call
     * this method with SOURCE_PROVIDER after validating the provider response.
     *
     * @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>}
     */
    public function confirm(
        int $transactionId,
        string $reference,
        ?User $actor,
        string $source,
        ?string $providerReference = null,
    ): array {
        return DB::transaction(function () use ($transactionId, $reference, $actor, $source, $providerReference): array {
            $transaction = Transaction::query()
                ->with(['branch:id,name', 'paymentMethod:id,name'])
                ->whereKey($transactionId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return $this->failure(404, 'Transaksi tidak ditemukan.');
            }

            if ($source === self::SOURCE_MANUAL
                && (! $actor
                    || (int) $transaction->branch_id !== (int) BranchScope::userBranchId($actor)
                    || (int) $transaction->user_id !== (int) $actor->id)) {
                return $this->failure(403, 'Anda tidak memiliki akses untuk mengonfirmasi transaksi ini.');
            }

            if (strtolower(trim((string) $transaction->paymentMethod?->name)) !== 'qris') {
                return $this->failure(409, 'Transaksi ini tidak menggunakan metode pembayaran QRIS.');
            }

            if ((string) $transaction->status === Transaction::STATUS_SUCCESS) {
                return $this->failure(409, 'Pembayaran QRIS sudah pernah dikonfirmasi.');
            }

            if ((string) $transaction->status !== Transaction::STATUS_PENDING_PAYMENT) {
                return $this->failure(409, 'Status transaksi tidak dapat dikonfirmasi sebagai pembayaran QRIS.');
            }

            $attempt = QrisPaymentAttempt::query()
                ->where('transaction_id', $transaction->id)
                ->where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $attempt || (string) $attempt->status !== QrisPaymentAttempt::STATUS_ACTIVE) {
                return $this->failure(409, 'Referensi QRIS sudah tidak aktif atau telah digunakan.');
            }

            $now = now(config('app.timezone', 'Asia/Jakarta'));
            if ($attempt->expires_at->lessThanOrEqualTo($now)) {
                $attempt->update(['status' => QrisPaymentAttempt::STATUS_EXPIRED]);

                return $this->failure(410, 'QRIS sudah kedaluwarsa. Buat QRIS baru untuk melanjutkan.');
            }

            $amount = (string) $transaction->getRawOriginal('total_amount');
            if ((string) $attempt->getRawOriginal('amount') !== $amount) {
                $attempt->update(['status' => QrisPaymentAttempt::STATUS_REPLACED]);

                return $this->failure(409, 'Nominal referensi QRIS tidak sesuai dengan total transaksi.');
            }

            $attempt->update([
                'status' => QrisPaymentAttempt::STATUS_CONFIRMED,
                'confirmed_at' => $now,
                'confirmed_by' => $actor?->id,
                'confirmation_source' => $source,
                'provider_reference' => $providerReference,
            ]);

            $transaction->update([
                'status' => Transaction::STATUS_SUCCESS,
                'paid_amount' => $amount,
                'change_amount' => 0,
                'payment_confirmed_at' => $now,
                'payment_confirmed_by' => $actor?->id,
                'payment_confirmation_source' => $source,
                'payment_provider_reference' => $providerReference,
            ]);

            $this->dailySalesSummaryService->recordSuccessfulTransaction(
                $transaction->branch,
                $transaction->created_at,
                (float) $amount,
                (int) $transaction->details()->sum('quantity'),
            );
            AdminCache::bumpTransactions();
            AdminCache::bumpDashboard();
            AdminCache::bumpCashflow();
            AdminCache::bumpUsage();

            return [
                'ok' => true,
                'data' => [
                    'transaction_id' => (int) $transaction->id,
                    'transaction_code' => (string) $transaction->transaction_code,
                    'status' => Transaction::STATUS_SUCCESS,
                    'amount' => (float) $amount,
                    'qris_reference' => (string) $attempt->reference,
                    'confirmed_at' => $now->toIso8601String(),
                    'confirmed_by' => $actor ? [
                        'id' => (int) $actor->id,
                        'name' => (string) $actor->name,
                    ] : null,
                    'confirmation_source' => $source,
                ],
            ];
        }, 3);
    }

    /** @return array{ok:false,status:int,message:string} */
    private function failure(int $status, string $message): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message];
    }
}
