<?php

namespace App\Http\Controllers\API;

use App\Actions\Payments\ConfirmQrisPaymentAction;
use App\Actions\Payments\GenerateQrisPaymentAction;
use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ConfirmQrisPaymentRequest;
use App\Http\Requests\API\GenerateQrisRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class QrisPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GenerateQrisPaymentAction $generateAction,
        private readonly ConfirmQrisPaymentAction $confirmAction,
    ) {}

    public function generate(GenerateQrisRequest $request)
    {
        try {
            $result = $this->generateAction->execute(
                (int) $request->validated('transaction_id'),
                $request->user(),
            );
        } catch (DecryptException|InvalidArgumentException $exception) {
            Log::warning('Konfigurasi QRIS cabang gagal digunakan.', [
                'operation' => 'qris_generate',
                'transaction_id' => (int) $request->validated('transaction_id'),
                'user_id' => (int) $request->user()->id,
                'exception' => get_class($exception),
            ]);

            return $this->errorResponse('Konfigurasi QRIS tidak valid.', null, 422);
        } catch (Throwable $exception) {
            Log::error('Gagal menghasilkan QRIS transaksi.', [
                'operation' => 'qris_generate',
                'transaction_id' => (int) $request->validated('transaction_id'),
                'user_id' => (int) $request->user()->id,
                'exception' => get_class($exception),
            ]);

            return $this->errorResponse('QRIS transaksi gagal dibuat. Silakan coba lagi.', null, 500);
        }

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], null, $result['status']);
        }

        return $this->successResponse('QRIS transaksi berhasil dibuat.', $result['data']);
    }

    public function confirm(ConfirmQrisPaymentRequest $request)
    {
        try {
            $result = $this->confirmAction->executeManual(
                (int) $request->validated('transaction_id'),
                (string) $request->validated('qris_reference'),
                $request->user(),
            );
        } catch (Throwable $exception) {
            Log::error('Gagal mengonfirmasi pembayaran QRIS.', [
                'operation' => 'qris_confirm',
                'transaction_id' => (int) $request->validated('transaction_id'),
                'user_id' => (int) $request->user()->id,
                'exception' => get_class($exception),
            ]);

            return $this->errorResponse('Pembayaran QRIS belum dapat dikonfirmasi. Silakan coba lagi.', null, 500);
        }

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], null, $result['status']);
        }

        return $this->successResponse('Pembayaran QRIS berhasil dikonfirmasi.', $result['data']);
    }
}
