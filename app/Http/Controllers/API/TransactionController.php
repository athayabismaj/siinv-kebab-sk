<?php

namespace App\Http\Controllers\API;

use App\Actions\Sales\CheckoutTransactionAction;
use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreTransactionRequest;
use App\Services\ApiTransactionService;
use App\Support\AdminCache;
use App\Support\BranchScope;
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
        private readonly CheckoutTransactionAction $checkoutTransactionAction,
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
            (int) $request->input('per_page', 15),
            $this->branchIdFor($request->user())
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
        $canReadAllUsers = in_array($roleName, ['admin', 'owner', 'superadmin', 'developer'], true);

        $transaction = $this->transactionService->getTransactionDetail(
            $transactionKey,
            $userId,
            $canReadAllUsers,
            $this->branchIdFor($user)
        );
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

        $summary = $this->transactionService->getRevenueSummary(
            $userId,
            $request->input('date'),
            $this->branchIdFor($request->user())
        );

        return $this->successResponse('Berhasil mengambil ringkasan pendapatan', $summary);
    }

    public function revenueTrend(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        $trend = $this->transactionService->getRevenueTrend(
            $userId,
            $request->input('date'),
            $this->branchIdFor($request->user())
        );

        return $this->successResponse('Berhasil mengambil tren pendapatan', $trend);
    }

    public function store(StoreTransactionRequest $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId <= 0) {
            return $this->unauthorizedResponse();
        }

        try {
            $validated = $request->validated();
            $checkout = $this->checkoutTransactionAction->execute($validated, $userId);
            if (! $checkout['ok']) {
                return $this->errorResponse(
                    $checkout['message'],
                    $checkout['data'] ?? null,
                    $checkout['status']
                );
            }

            $result = $checkout['result'];

            AdminCache::bumpDashboard();
            AdminCache::bumpCashflow();
            AdminCache::bumpUsage();
            AdminCache::bumpDailyStock();
            AdminCache::bumpTransactions();

            return $this->successResponse('Transaksi berhasil', $result, 201);
        } catch (Throwable $e) {
            try {
                Log::error('Gagal memproses transaksi kasir.', [
                    'operation' => 'checkout',
                    'user_id' => $userId,
                    'branch_id' => $this->branchIdFor($request->user()),
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

    private function branchIdFor(mixed $user): ?int
    {
        $roleName = strtolower(trim((string) optional(optional($user)->role)->name));

        if (in_array($roleName, ['owner', 'superadmin', 'developer'], true)) {
            return null;
        }

        return $user ? BranchScope::userBranchId($user) : null;
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
