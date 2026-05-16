<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CloseSessionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseSessionController extends Controller
{
    public function __construct(
        private readonly CloseSessionServiceInterface $closeSessionService
    ) {}

    public function close(Request $request, $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'actual_physical_cash' => 'required|numeric|min:0',
            'closing_notes'       => 'nullable|string|max:500',
        ]);

        try {
            $session = $this->closeSessionService->closeSession(
                sessionId: $sessionId,
                actualCash: (float) $validated['actual_physical_cash'],
                closedBy: $request->user()->id,
            );

            // Append closing_notes jika ada dari kasir
            if (!empty($validated['closing_notes'])) {
                $session->notes = trim($session->notes . "\n[KASIR] " . $validated['closing_notes']);
                $session->save();
            }

            return response()->json([
                'message' => 'Sesi kasir berhasil ditutup.',
                'data' => [
                    'system_cash'   => (float) $session->system_cash,
                    'actual_cash'   => (float) $session->actual_cash,
                    'variance'      => (float) $session->cash_variance,
                ],
            ], 200);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'UnreconciledInventoryException')) {
                return response()->json([
                    'message' => 'Tidak dapat menutup shift. Ada bahan baku yang belum dihitung sisa fisik akhirnya.',
                ], 422);
            }

            if (str_contains($e->getMessage(), 'SessionAlreadyClosedException')) {
                return response()->json([
                    'message' => 'Sesi kasir ini sudah ditutup sebelumnya.',
                ], 409);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
