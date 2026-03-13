<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreTransactionRequest;
use App\Models\PaymentMethod;
use App\Services\ApiTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApiTransactionService $transactionService
    ) {
    }

    public function index(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $transactions = $this->transactionService->getHistory(
            $userId,
            $request->input('date'),
            (int) $request->input('per_page', 15)
        );

        $data = $transactions->map(fn ($transaction) => [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'total_amount' => (float) $transaction->total_amount,
            'status' => 'Sukses',
            'created_at' => \Carbon\Carbon::parse($transaction->created_at)->isoFormat('D MMMM Y HH:mm'),
            'items_count' => (int) $transaction->items_count,
        ]);

        return $this->successResponse('Berhasil mengambil riwayat transaksi', [
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'data' => $data,
        ]);
    }

    public function revenueSummary(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $summary = $this->transactionService->getRevenueSummary($userId, $request->input('date'));

        return $this->successResponse('Berhasil mengambil ringkasan pendapatan', $summary);
    }

    public function revenueTrend(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $trend = $this->transactionService->getRevenueTrend($userId, $request->input('date'));

        return $this->successResponse('Berhasil mengambil tren pendapatan', $trend);
    }

    public function store(StoreTransactionRequest $request)
    {
        if (! PaymentMethod::query()->whereNull('deleted_at')->exists()) {
            return $this->errorResponse('Metode pembayaran belum tersedia.', [
                'payment_method_id' => $request->input('payment_method_id'),
            ], 422);
        }

        $validated = $request->validated();
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $draft = $this->transactionService->buildCheckoutDraft($validated);
        if (! $draft['ok']) {
            return $this->errorResponse(
                $draft['message'],
                $draft['data'] ?? null,
                $draft['status']
            );
        }

        try {
            $result = $this->transactionService->createCheckoutTransaction(
                $userId,
                $draft,
                $validated['note'] ?? null
            );

            return $this->successResponse('Transaksi berhasil', $result, 201);
        } catch (Throwable $e) {
            Log::error('Gagal memproses transaksi kasir.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException) {
                return $this->errorResponse($e->getMessage(), null, 409);
            }

            if ($e instanceof QueryException) {
                $sqlState = $e->errorInfo[0] ?? null;
                $isDbTimeout = in_array($sqlState, ['57014', '55P03', '08006'], true);
                if ($isDbTimeout) {
                    return $this->errorResponse('Database sedang sibuk/tidak stabil. Silakan coba lagi.', null, 503);
                }
            }

            return $this->errorResponse('Transaksi gagal diproses. Silakan coba lagi.', null, 500);
        }
    }

    private function resolveUserId(Request $request): int
    {
        return (int) optional($request->user())->id;
    }
}
