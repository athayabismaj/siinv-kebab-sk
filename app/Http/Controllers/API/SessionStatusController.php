<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Api\CashierOperationalContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionStatusController extends Controller
{
    public function __construct(
        private readonly CashierOperationalContextResolver $operationalContextResolver,
    ) {
    }

    public function currentStatus(Request $request): JsonResponse
    {
        $context = $this->operationalContextResolver->resolve($request->user());

        if ($context->ambiguous) {
            return response()->json([
                'active' => false,
                'message' => 'Terdapat konflik sesi aktif. Hubungi admin untuk memeriksa sesi kasir.',
            ], 409);
        }

        $session = $context->session;

        if (! $session) {
            return response()->json([
                'active' => false,
                'message' => 'Tidak ada sesi aktif untuk user ini.',
            ], 404);
        }

        return response()->json([
            'active' => true,
            'data' => [
                'session_id' => $session->id,
                'status' => $session->status,
                'opened_at' => $session->opened_at,
                'session_date' => $session->session_date,
            ],
        ], 200);
    }
}
