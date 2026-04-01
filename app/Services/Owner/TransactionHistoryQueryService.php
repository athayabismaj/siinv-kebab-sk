<?php

namespace App\Services\Owner;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TransactionHistoryQueryService
{
    public function baseFilterQuery(Carbon $dateFrom, Carbon $dateTo): Builder
    {
        return Transaction::query()
            ->whereBetween('transactions.created_at', [
                $dateFrom->copy()->startOfDay()->toDateTimeString(),
                $dateTo->copy()->endOfDay()->toDateTimeString(),
            ]);
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
                'created_at',
            ])
            ->with(['user:id,name,username', 'paymentMethod:id,name'])
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

        return $query;
    }

    public function summary(Carbon $dateFrom, Carbon $dateTo, array $filters): array
    {
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

    public function topCashierName(Carbon $dateFrom, Carbon $dateTo, array $filters): string
    {
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
}

