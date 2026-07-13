<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreTransactionRequest;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\Analytics\DailySalesSummaryService;
use App\Services\ApiTransactionService;
use App\Support\AdminCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApiTransactionService $transactionService,
        private readonly DailySalesSummaryService $dailySalesSummaryService
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
            'status' => strtolower($transaction->status ?? 'success') === 'success' ? 'Sukses' : ucfirst(str_replace('_', ' ', $transaction->status)),
            'created_at' => $this->formatCreatedAt($transaction->created_at),
            'items_count' => (int) $transaction->items_count,
        ]);

        return $this->successResponse('Berhasil mengambil riwayat transaksi', [
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'data' => $data,
        ]);
    }

    public function show(Request $request, string $transactionKey)
    {
        $user = $request->user();
        $userId = (int) optional($user)->id;
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $roleName = strtolower(trim((string) optional($user->role)->name));
        $canReadAll = in_array($roleName, ['admin', 'owner', 'superadmin', 'developer'], true);

        $transaction = $this->transactionService->getTransactionDetail($transactionKey, $userId, $canReadAll);
        if (! $transaction) {
            return $this->errorResponse('Transaksi tidak ditemukan.', null, 404);
        }

        return $this->successResponse('Berhasil mengambil detail transaksi', $transaction);
    }

    public function receipt(Request $request, string $transactionKey)
    {
        return $this->show($request, $transactionKey);
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
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        try {
            if (! PaymentMethod::query()->whereNull('deleted_at')->exists()) {
                return $this->errorResponse('Metode pembayaran belum tersedia.', [
                    'payment_method_id' => $request->input('payment_method_id'),
                ], 422);
            }

            $validated = $request->validated();

            $draft = $this->transactionService->buildCheckoutDraft($validated, $userId);
            if (! $draft['ok']) {
                return $this->errorResponse(
                    $draft['message'],
                    $draft['data'] ?? null,
                    $draft['status']
                );
            }

            $result = $this->transactionService->createCheckoutTransaction(
                $userId,
                $draft,
                $validated['note'] ?? null
            );

            AdminCache::bumpDashboard();
            AdminCache::bumpCashflow();
            AdminCache::bumpUsage();
            AdminCache::bumpDailyStock();
            AdminCache::bumpTransactions();
            $branchId = (int) Transaction::query()
                ->whereKey($result['transaction_id'])
                ->value('branch_id');
            $branch = Branch::query()->findOrFail($branchId);
            $this->dailySalesSummaryService->rebuildForDate($branch, now());

            return $this->successResponse('Transaksi berhasil', $result, 201);
        } catch (Throwable $e) {
            try {
                Log::error('Gagal memproses transaksi kasir.', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            } catch (Throwable) {
                // Logging failure should never break checkout error response.
            }

            if ($e instanceof RuntimeException) {
                return $this->errorResponse('Transaksi gagal diproses. Periksa stok harian dan data transaksi lalu coba lagi.', null, 409);
            }

            if ($e instanceof ModelNotFoundException) {
                return $this->errorResponse('Data pembayaran atau varian menu tidak ditemukan. Muat ulang menu lalu coba lagi.', null, 422);
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

    private function formatCreatedAt(mixed $createdAt): string
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');

        try {
            return Carbon::parse($createdAt, 'UTC')
                ->setTimezone($timezone)
                ->isoFormat('D MMMM Y HH:mm');
        } catch (Throwable) {
            return Carbon::now($timezone)->isoFormat('D MMMM Y HH:mm');
        }
    }
}
