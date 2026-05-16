<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionStatusController extends Controller
{
    public function currentStatus(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Query efisien: index-friendly, limit 1, tanpa eager load yang tidak perlu
        $session = DailyStockSession::where('cashier_id', $userId)
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->first(['id', 'status', 'opened_at', 'session_date']);

        if (!$session) {
            return response()->json([
                'active' => false,
                'message' => 'Tidak ada sesi aktif untuk user ini.',
            ], 404);
        }

        return response()->json([
            'active' => true,
            'data' => [
                'session_id'   => $session->id,
                'status'       => $session->status,
                'opened_at'    => $session->opened_at,
                'session_date' => $session->session_date,
            ],
        ], 200);
    }
}
