<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiTransactionService
{
    public function getHistory(int $userId, ?string $date, int $perPage = 15, int|array|null $branchId = null): LengthAwarePaginator
    {
        $query = $this->baseUserTransactionQuery($userId, $date, $branchId)
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

    public function getTransactionDetail(string $transactionKey, int $userId, bool $canReadAll = false, int|array|null $branchId = null): ?array
    {
        $transactionQuery = Transaction::query()
            ->with([
                'paymentMethod:id,name',
                'branch:id,name,address',
                'details.menu:id,name',
                'details.menuVariant:id,name',
            ])
            ->when(
                ctype_digit($transactionKey),
                fn ($query) => $query->where('id', (int) $transactionKey),
                fn ($query) => $query->where('transaction_code', $transactionKey),
            )
            ->when(! $canReadAll, fn ($query) => $query->where('user_id', $userId));
        $this->applyBranchScope($transactionQuery, $branchId);
        $transaction = $transactionQuery->first();

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
            'branch' => $transaction->branch ? [
                'id' => (int) $transaction->branch->id,
                'name' => (string) $transaction->branch->name,
                'address' => $transaction->branch->address,
            ] : null,
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

    public function getRevenueSummary(int $userId, ?string $date, ?int $branchId = null): array
    {
        $selectedDate = $this->resolveDateOrNow($date)->toDateString();
        $query = $this->baseUserTransactionQuery($userId, $selectedDate, $branchId)
            ->whereRaw("UPPER(COALESCE(t.status, '')) = ?", ['SUCCESS']);

        $totalRevenue = (float) (clone $query)->sum('t.total_amount');
        $totalCount = (int) (clone $query)->count();

        $dominantItemName = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('menus', 'menus.id', '=', 'td.menu_id')
            ->where('t.user_id', $userId)
            ->when($branchId, fn ($query) => $query->where('t.branch_id', $branchId))
            ->when(! empty($selectedDate), function ($query) use ($selectedDate) {
                $start = Carbon::parse($selectedDate)->startOfDay();
                $end = Carbon::parse($selectedDate)->endOfDay();
                $query->whereBetween('t.created_at', [$start, $end]);
            })
            ->whereRaw("UPPER(COALESCE(t.status, '')) = ?", ['SUCCESS'])
            ->select('menus.name', DB::raw('SUM(td.quantity) as total_qty'))
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('total_qty')
            ->first()
            ?->name;

        return [
            'date' => $selectedDate,
            'total_revenue' => $totalRevenue,
            'total_count' => $totalCount,
            'dominant_item_name' => $dominantItemName,
        ];
    }

    public function getRevenueTrend(int $userId, ?string $dateInput, ?int $branchId = null): array
    {
        $endDate = $this->resolveDateOrNow($dateInput);
        $startDate = $endDate->copy()->subDays(6);

        $trendData = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total_revenue'))
            ->where('user_id', $userId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->whereRaw("UPPER(COALESCE(status, '')) = ?", ['SUCCESS'])
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

    private function baseUserTransactionQuery(int $userId, ?string $date, int|array|null $branchId = null): Builder
    {
        $query = DB::table('transactions as t')->where('t.user_id', $userId);

        $this->applyBranchScope($query, $branchId, 't.branch_id');

        if (! empty($date)) {
            $start = Carbon::parse($date)->startOfDay();
            $end = Carbon::parse($date)->endOfDay();
            $query->whereBetween('t.created_at', [$start, $end]);
        }

        return $query;
    }

    private function applyBranchScope(
        Builder|EloquentBuilder $query,
        int|array|null $branchId,
        string $column = 'branch_id',
    ): void {
        if (is_array($branchId)) {
            $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchId))));
            $query->whereIn($column, $branchIds === [] ? [-1] : $branchIds);

            return;
        }

        if (($branchId ?? 0) > 0) {
            $query->where($column, (int) $branchId);
        }
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
