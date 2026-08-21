<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\GenerateQrisRequest;
use App\Models\QrisConfig;
use App\Models\Transaction;
use App\Services\QrisService;
use App\Support\BranchScope;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class QrisPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly QrisService $qrisService) {}

    public function generate(GenerateQrisRequest $request)
    {
        $user = $request->user();
        $transaction = Transaction::query()
            ->with([
                'branch:id,name',
                'paymentMethod:id,name',
            ])
            ->find((int) $request->validated('transaction_id'));

        if (! $transaction) {
            return $this->errorResponse('Transaksi tidak ditemukan.', null, 404);
        }

        $cashierBranchId = BranchScope::userBranchId($user);
        if ((int) $transaction->branch_id !== (int) $cashierBranchId
            || (int) $transaction->user_id !== (int) $user->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke transaksi cabang ini.', null, 403);
        }

        if (strtolower(trim((string) $transaction->paymentMethod?->name)) !== 'qris') {
            return $this->errorResponse('Transaksi ini tidak menggunakan metode pembayaran QRIS.', null, 409);
        }

        if (strtoupper(trim((string) $transaction->status)) !== 'SUCCESS') {
            return $this->errorResponse('Status transaksi tidak dapat digunakan untuk membuat QRIS.', null, 409);
        }

        $config = QrisConfig::query()
            ->where('branch_id', $transaction->branch_id)
            ->active()
            ->first();

        if (! $config) {
            return $this->errorResponse('QRIS belum dikonfigurasi untuk cabang ini.', null, 404);
        }

        try {
            $dynamicPayload = $this->qrisService->generateDynamic(
                (string) $config->qris_payload,
                (string) $transaction->getRawOriginal('total_amount'),
            );
        } catch (DecryptException|InvalidArgumentException $exception) {
            Log::warning('Konfigurasi QRIS cabang gagal digunakan.', [
                'operation' => 'qris_generate',
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id,
                'qris_config_id' => $config->id,
                'exception' => get_class($exception),
            ]);

            return $this->errorResponse('Konfigurasi QRIS tidak valid.', null, 422);
        } catch (Throwable $exception) {
            Log::error('Gagal menghasilkan QRIS transaksi.', [
                'operation' => 'qris_generate',
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id,
                'exception' => get_class($exception),
            ]);

            return $this->errorResponse('QRIS transaksi gagal dibuat. Silakan coba lagi.', null, 500);
        }

        $amount = (string) $transaction->getRawOriginal('total_amount');
        $amount = str_ends_with($amount, '.00') ? substr($amount, 0, -3) : $amount;

        return $this->successResponse('QRIS transaksi berhasil dibuat.', [
            'transaction_id' => (int) $transaction->id,
            'branch_id' => (int) $transaction->branch_id,
            'branch_name' => (string) $transaction->branch?->name,
            'merchant_name' => (string) ($config->merchant_display_name ?: $config->merchant_name),
            'amount' => ctype_digit($amount) ? (int) $amount : $amount,
            'qris_payload' => $dynamicPayload,
        ]);
    }
}
