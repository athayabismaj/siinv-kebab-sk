<?php

namespace App\Actions\Payments;

use App\Models\QrisConfig;
use App\Models\QrisPaymentAttempt;
use App\Models\Transaction;
use App\Models\User;
use App\Services\QrisService;
use App\Support\BranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateQrisPaymentAction
{
    public function __construct(private readonly QrisService $qrisService) {}

    /** @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>} */
    public function execute(int $transactionId, User $actor): array
    {
        return DB::transaction(function () use ($transactionId, $actor): array {
            $transaction = Transaction::query()
                ->with(['branch:id,name', 'paymentMethod:id,name'])
                ->whereKey($transactionId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return $this->failure(404, 'Transaksi tidak ditemukan.');
            }

            if ((int) $transaction->branch_id !== (int) BranchScope::userBranchId($actor)
                || (int) $transaction->user_id !== (int) $actor->id) {
                return $this->failure(403, 'Anda tidak memiliki akses ke transaksi cabang ini.');
            }

            if (! $this->isQris($transaction)) {
                return $this->failure(409, 'Transaksi ini tidak menggunakan metode pembayaran QRIS.');
            }

            if ((string) $transaction->status !== Transaction::STATUS_PENDING_PAYMENT) {
                return $this->failure(409, 'QRIS hanya dapat dibuat untuk transaksi yang menunggu pembayaran.');
            }

            $config = QrisConfig::query()
                ->where('branch_id', $transaction->branch_id)
                ->active()
                ->first();

            if (! $config) {
                return $this->failure(404, 'QRIS belum dikonfigurasi untuk cabang ini.');
            }

            $amount = (string) $transaction->getRawOriginal('total_amount');
            $now = now(config('app.timezone', 'Asia/Jakarta'));

            $attempt = QrisPaymentAttempt::query()
                ->where('transaction_id', $transaction->id)
                ->where('status', QrisPaymentAttempt::STATUS_ACTIVE)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($attempt && $attempt->expires_at->lessThanOrEqualTo($now)) {
                $attempt->update(['status' => QrisPaymentAttempt::STATUS_EXPIRED]);
                $attempt = null;
            }

            if ($attempt && (string) $attempt->getRawOriginal('amount') !== $amount) {
                $attempt->update(['status' => QrisPaymentAttempt::STATUS_REPLACED]);
                $attempt = null;
            }

            $reference = $attempt?->reference ?: $this->uniqueReference();
            $dynamicPayload = $this->qrisService->generateDynamic(
                (string) $config->qris_payload,
                $amount,
                $reference,
            );
            $payloadHash = hash('sha256', $dynamicPayload);

            if ($attempt && ! hash_equals((string) $attempt->payload_hash, $payloadHash)) {
                $attempt->update(['status' => QrisPaymentAttempt::STATUS_REPLACED]);
                $attempt = null;
                $reference = $this->uniqueReference();
                $dynamicPayload = $this->qrisService->generateDynamic(
                    (string) $config->qris_payload,
                    $amount,
                    $reference,
                );
                $payloadHash = hash('sha256', $dynamicPayload);
            }

            if (! $attempt) {
                $attempt = QrisPaymentAttempt::query()->create([
                    'transaction_id' => $transaction->id,
                    'reference' => $reference,
                    'amount' => $amount,
                    'payload_hash' => $payloadHash,
                    'status' => QrisPaymentAttempt::STATUS_ACTIVE,
                    'generated_at' => $now,
                    'expires_at' => $now->copy()->addSeconds((int) config('qris.payment_expiry_seconds', 300)),
                ]);
            }

            $displayAmount = str_ends_with($amount, '.00') ? substr($amount, 0, -3) : $amount;

            return [
                'ok' => true,
                'data' => [
                    'transaction_id' => (int) $transaction->id,
                    'branch_id' => (int) $transaction->branch_id,
                    'branch_name' => (string) $transaction->branch?->name,
                    'merchant_name' => (string) ($config->merchant_display_name ?: $config->merchant_name),
                    'amount' => ctype_digit($displayAmount) ? (int) $displayAmount : $displayAmount,
                    'qris_payload' => $dynamicPayload,
                    'qris_reference' => (string) $attempt->reference,
                    'generated_at' => $attempt->generated_at->toIso8601String(),
                    'expires_at' => $attempt->expires_at->toIso8601String(),
                ],
            ];
        }, 3);
    }

    private function isQris(Transaction $transaction): bool
    {
        return strtolower(trim((string) $transaction->paymentMethod?->name)) === 'qris';
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'QRS-'.Str::upper(Str::random(20));
        } while (QrisPaymentAttempt::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /** @return array{ok:false,status:int,message:string} */
    private function failure(int $status, string $message): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message];
    }
}
