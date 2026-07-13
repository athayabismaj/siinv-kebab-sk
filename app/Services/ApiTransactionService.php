<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DailyTarget;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiTransactionService
{
    public function getHistory(int $userId, ?string $date, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseUserTransactionQuery($userId, $date)
            ->leftJoin('transaction_details as td', 'td.transaction_id', '=', 't.id')
            ->select([
                't.id',
                't.transaction_code',
                't.total_amount',
                't.status',
                't.created_at',
                DB::raw('COUNT(td.id) as items_count'),
            ])
            ->groupBy('t.id', 't.transaction_code', 't.total_amount', 't.status', 't.created_at')
            ->orderByDesc('t.created_at');

        return $query->paginate($perPage);
    }

    public function getTransactionDetail(string $transactionKey, int $userId, bool $canReadAll = false): ?array
    {
        $transaction = Transaction::query()
            ->with([
                'paymentMethod:id,name',
                'details.menu:id,name',
                'details.menuVariant:id,name',
            ])
            ->when(
                ctype_digit($transactionKey),
                fn ($query) => $query->where('id', (int) $transactionKey),
                fn ($query) => $query->where('transaction_code', $transactionKey),
            )
            ->when(! $canReadAll, fn ($query) => $query->where('user_id', $userId))
            ->first();

        if (! $transaction) {
            return null;
        }

        $timezone = config('app.timezone', 'Asia/Jakarta');

        return [
            'id' => (int) $transaction->id,
            'transaction_code' => (string) $transaction->transaction_code,
            'created_at' => Carbon::parse($transaction->created_at)->setTimezone($timezone)->format('Y-m-d H:i:s'),
            'status' => strtoupper((string) ($transaction->status ?? 'SUCCESS')),
            'payment_method_name' => $transaction->paymentMethod?->name,
            'total_amount' => round((float) $transaction->total_amount, 2),
            'paid_amount' => round((float) $transaction->paid_amount, 2),
            'change_amount' => round((float) $transaction->change_amount, 2),
            'items' => $transaction->details
                ->map(fn ($detail) => [
                    'menu_name' => $detail->menu?->name,
                    'variant_name' => $detail->menuVariant?->name,
                    'qty' => (int) $detail->quantity,
                    'price' => round((float) $detail->price, 2),
                    'subtotal' => round((float) $detail->subtotal, 2),
                ])
                ->values(),
        ];
    }

    public function getRevenueSummary(int $userId, ?string $date): array
    {
        $selectedDate = $this->resolveDateOrNow($date)->toDateString();
        $query = $this->baseUserTransactionQuery($userId, $selectedDate)
            ->where(function (Builder $query) {
                $query->whereNull('t.status')
                    ->orWhereRaw('LOWER(t.status) <> ?', ['void']);
            });

        $totalRevenue = (float) (clone $query)->sum('t.total_amount');
        $totalCount = (int) (clone $query)->count();

        $dominantItemName = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('menus', 'menus.id', '=', 'td.menu_id')
            ->where('t.user_id', $userId)
            ->when(! empty($selectedDate), function ($query) use ($selectedDate) {
                $start = Carbon::parse($selectedDate)->startOfDay();
                $end = Carbon::parse($selectedDate)->endOfDay();
                $query->whereBetween('t.created_at', [$start, $end]);
            })
            ->where(function ($query) {
                $query->whereNull('t.status')
                    ->orWhereRaw('LOWER(t.status) <> ?', ['void']);
            })
            ->select('menus.name', DB::raw('SUM(td.quantity) as total_qty'))
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('total_qty')
            ->first()
            ?->name;

        $targetRevenue = 0.0;
        $targetTransactions = 0;

        if (Schema::hasTable('daily_targets')) {
            $targetQuery = DailyTarget::query()
                ->whereDate('target_date', '<=', $selectedDate)
                ->orderByDesc('target_date');

            if (Schema::hasColumn('daily_targets', 'branch_id')) {
                $cashier = User::query()->find($userId, ['id', 'branch_id']);
                $branchId = BranchScope::userBranchId($cashier);

                if ($branchId) {
                    $targetQuery->where('branch_id', $branchId);
                }
            }

            $target = $targetQuery->first(['target_revenue', 'target_transactions']);
            $targetRevenue = (float) ($target->target_revenue ?? 0);
            $targetTransactions = (int) ($target->target_transactions ?? 0);
        }

        $revenueAchievedPct = $targetRevenue > 0
            ? round(($totalRevenue / $targetRevenue) * 100, 1)
            : 0.0;
        $transactionAchievedPct = $targetTransactions > 0
            ? round(($totalCount / $targetTransactions) * 100, 1)
            : 0.0;

        return [
            'date' => $selectedDate,
            'total_revenue' => $totalRevenue,
            'total_count' => $totalCount,
            'dominant_item_name' => $dominantItemName,
            'target_revenue' => $targetRevenue,
            'target_count' => $targetTransactions,
            'target_achieved_pct' => $revenueAchievedPct,
            'target_count_achieved_pct' => $transactionAchievedPct,
            'target_harian' => $targetRevenue,
            'target_transactions' => $targetTransactions,
            'target_percentage' => $revenueAchievedPct,
            'target_progress_percent' => $revenueAchievedPct,
            'achievement_percentage' => $revenueAchievedPct,
            'transaction_target_percentage' => $transactionAchievedPct,
            'target' => [
                'revenue' => $targetRevenue,
                'transactions' => $targetTransactions,
                'revenue_achieved_pct' => $revenueAchievedPct,
                'transactions_achieved_pct' => $transactionAchievedPct,
            ],
        ];
    }

    public function getRevenueTrend(int $userId, ?string $dateInput): array
    {
        $endDate = $this->resolveDateOrNow($dateInput);
        $startDate = $endDate->copy()->subDays(6);

        $trendData = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total_revenue'))
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['void']);
            })
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($index = 0; $index < 7; $index++) {
            $currentDate = $startDate->copy()->addDays($index)->format('Y-m-d');
            $result[] = [
                'date' => $currentDate,
                'total_revenue' => (float) ($trendData->has($currentDate) ? $trendData[$currentDate]->total_revenue : 0),
            ];
        }

        return $result;
    }

    private function baseUserTransactionQuery(int $userId, ?string $date): Builder
    {
        $query = DB::table('transactions as t')->where('t.user_id', $userId);

        if (! empty($date)) {
            $start = Carbon::parse($date)->startOfDay();
            $end = Carbon::parse($date)->endOfDay();
            $query->whereBetween('t.created_at', [$start, $end]);
        }

        return $query;
    }

    private function resolveDateOrNow(?string $dateInput): Carbon
    {
        try {
            return $dateInput ? Carbon::parse($dateInput) : Carbon::now();
        } catch (\Throwable) {
            return Carbon::now();
        }
    }
}
