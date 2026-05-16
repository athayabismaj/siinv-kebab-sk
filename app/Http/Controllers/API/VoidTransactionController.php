<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Services\VoidTransactionServiceInterface;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use Illuminate\Http\JsonResponse;

class VoidTransactionController extends Controller
{
    public function __construct(
        private readonly VoidTransactionServiceInterface $voidService
    ) {}

    public function voidTransaction(Request $request, $transactionId): JsonResponse
    {
        // Fail-Fast Validation
        $validated = $request->validate([
            'current_session_id' => 'required|integer',
            'inventory_action' => 'required|in:restock,waste',
        ]);

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');
        if (!$idempotencyKey) {
            return response()->json(['message' => 'Idempotency Key is missing dari Header atau Body.'], 400);
        }

        try {
            // Mapping ke DTO Readonly
            $dto = new VoidTransactionRequestDto(
                transactionId: $transactionId,
                currentSessionId: $validated['current_session_id'],
                actor: $request->user(),
                idempotencyKey: $idempotencyKey,
                inventoryAction: VoidInventoryActionEnum::from($validated['inventory_action'])
            );

            // Eksekusi Service (Murni, aman dari deadlock, mengembalikan float absolut)
            $newDrawerBalance = $this->voidService->voidTransaction($dto);

            // Return Single Source of Truth ke Android
            return response()->json([
                'message' => 'Transaksi berhasil dibatalkan.',
                'data' => [
                    'new_drawer_balance' => $newDrawerBalance
                ]
            ], 200);

        } catch (\Exception $e) {
            // Handler khusus jika conflict (Idempotency tertabrak)
            if (str_contains($e->getMessage(), 'Idempotency conflict')) {
                return response()->json(['message' => 'Permintaan sedang diproses, harap tunggu.'], 409);
            }
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
