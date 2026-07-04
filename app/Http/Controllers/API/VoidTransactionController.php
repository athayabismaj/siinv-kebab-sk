<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Services\VoidTransactionServiceInterface;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VoidTransactionController extends Controller
{
    public function __construct(
        private readonly VoidTransactionServiceInterface $voidService
    ) {}

    public function voidTransaction(Request $request, $transactionId): JsonResponse
    {
        // Fail-Fast Validation — eksplisit menerima JSON dari Android
        $validated = $request->validate([
            'current_session_id' => 'required|integer',
            'reason' => 'required|string|in:restock,waste',
        ], [
            'reason.required' => 'Alasan pembatalan (reason) wajib diisi.',
            'reason.in' => 'Alasan pembatalan harus berupa "restock" atau "waste".',
            'current_session_id.required' => 'ID sesi kasir aktif wajib dikirim.',
            'current_session_id.integer' => 'ID sesi kasir harus berupa angka.',
        ]);

        // Validasi Idempotency Key
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');
        if (!$idempotencyKey) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency Key wajib dikirim melalui header X-Idempotency-Key atau body idempotency_key.',
            ], 400);
        }

        try {
            $dto = new VoidTransactionRequestDto(
                transactionId: (int) $transactionId,
                currentSessionId: (int) $validated['current_session_id'],
                actor: $request->user(),
                idempotencyKey: $idempotencyKey,
                inventoryAction: VoidInventoryActionEnum::from($validated['reason'])
            );

            $newDrawerBalance = $this->voidService->voidTransaction($dto);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan.',
                'data' => [
                    'new_drawer_balance' => $newDrawerBalance,
                ],
            ], 200);

        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Idempotency conflict')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan sedang diproses, harap tunggu.',
                ], 409);
            }

            if (str_contains($e->getMessage(), 'sudah dibatalkan sebelumnya')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi sudah dibatalkan sebelumnya.',
                ], 409);
            }

            if (str_contains($e->getMessage(), 'Unauthorized')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan.',
                ], 403);
            }

            Log::error('Gagal membatalkan transaksi via API.', [
                'transaction_id' => (int) $transactionId,
                'actor_id' => optional($request->user())->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
            ], 500);
        }
    }
}
