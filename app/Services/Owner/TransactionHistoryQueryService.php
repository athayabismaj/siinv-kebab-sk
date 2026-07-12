<?php

namespace App\Services\Owner;

use App\Models\Transaction;
use App\Support\AdminCache;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryQueryService
{
    public function baseFilterQuery(Carbon $dateFrom, Carbon $dateTo): Builder
    {
        $from = $dateFrom->copy()->startOfDay();
        $to = $dateTo->copy()->endOfDay();

        return Transaction::query()
            ->whereBetween('transactions.created_at', [$from, $to]);
    }

    public function baseListQuery(Carbon $dateFrom, Carbon $dateTo): Builder
    {
        return $this->baseFilterQuery($dateFrom, $dateTo)
            ->select([
                'id',
                'transaction_code',
                'user_id',
                'payment_method_id',
                'total_amount',
                'paid_amount',
                'change_amount',
                'status',
                'void_reason',
                'voided_at',
                'voided_by',
                'created_at',
            ])
            ->with(['user:id,name,username', 'voidedBy:id,name,username', 'paymentMethod:id,name'])
            ->withCount('details');
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', (int) $filters['payment_method_id']);
        }

        if (!empty($filters['branch_id'])) {
            BranchScope::apply($query, (int) $filters['branch_id'], 'transactions.branch_id');
        }

        return $query;
    }

    public function summary(Carbon $dateFrom, Carbon $dateTo, array $filters): array
    {
        return Cache::remember(
            $this->key('summary', $dateFrom, $dateTo, $filters),
            now()->addSeconds(90),
            function () use ($dateFrom, $dateTo, $filters) {
                $aggregateRow = $this->applyFilters(
                    $this->baseFilterQuery($dateFrom, $dateTo),
                    $filters
                )
                    ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
                    ->first();

                $totalTransactions = (int) ($aggregateRow->total_transactions ?? 0);
                $totalRevenue = (float) ($aggregateRow->total_revenue ?? 0);

                return [
                    'total_transactions' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'avg_transaction' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
                ];
            }
        );
    }

    public function topCashierName(Carbon $dateFrom, Carbon $dateTo, array $filters): string
    {
        return Cache::remember(
            $this->key('top_cashier', $dateFrom, $dateTo, $filters),
            now()->addSeconds(90),
            function () use ($dateFrom, $dateTo, $filters) {
                $topCashierRow = $this->applyFilters(
                    $this->baseFilterQuery($dateFrom, $dateTo),
                    $filters
                )
                    ->join('users', 'users.id', '=', 'transactions.user_id')
                    ->selectRaw('transactions.user_id, users.name as cashier_name, COUNT(*) as trx_count')
                    ->groupBy('transactions.user_id', 'users.name')
                    ->orderByDesc('trx_count')
                    ->first();

                return (string) ($topCashierRow->cashier_name ?? '-');
            }
        );
    }

    private function key(string $metric, Carbon $dateFrom, Carbon $dateTo, array $filters): string
    {
        return AdminCache::key('transactions', 'owner:transaction_history:' . $metric . ':' . md5(json_encode([
            'from' => $dateFrom->toDateString(),
            'to' => $dateTo->toDateString(),
            'search' => trim((string) ($filters['search'] ?? '')),
            'user_id' => (string) ($filters['user_id'] ?? ''),
            'payment_method_id' => (string) ($filters['payment_method_id'] ?? ''),
            'branch_id' => (string) ($filters['branch_id'] ?? ''),
        ])));
    }
}

