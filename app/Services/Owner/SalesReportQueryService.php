<?php

namespace App\Services\Owner;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportQueryService
{
    public function buildDailySummary(Carbon $selectedDate): array
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($selectedDate, $selectedDate);
        $aggregate = Transaction::query()
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        $totalTransactions = (int) ($aggregate->total_transactions ?? 0);
        $totalRevenue = (float) ($aggregate->total_revenue ?? 0);
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $menuStats = $this->buildPeriodMenuStats($selectedDate, $selectedDate);

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'totalMenuSold' => (int) $menuStats->sum('total_qty'),
        ];
    }

    public function buildMonthlySummary(Carbon $selectedMonth): array
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange(
            $selectedMonth->copy()->startOfMonth(),
            $selectedMonth->copy()->endOfMonth()
        );

        $query = Transaction::query()
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        $totalTransactions = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $dailyBreakdown = DB::table('transactions')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, SUM(total_amount) as revenue')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'dailyBreakdown' => $dailyBreakdown,
        ];
    }

    public function buildWeeklySummary(Carbon $weekAnchor): array
    {
        $weekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($weekStart, $weekEnd);

        $query = Transaction::query()
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        $totalTransactions = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $weeklyBreakdown = DB::table('transactions')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, SUM(total_amount) as revenue')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'weeklyBreakdown' => $weeklyBreakdown,
        ];
    }

    public function buildYearlySummary(int $year): array
    {
        $query = Transaction::query()
            ->whereYear('created_at', $year);

        $totalTransactions = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $monthlyBreakdown = DB::table('transactions')
            ->selectRaw('EXTRACT(MONTH FROM created_at) as month, COUNT(*) as trx_count, SUM(total_amount) as revenue')
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('EXTRACT(MONTH FROM created_at)'))
            ->orderBy('month', 'asc')
            ->get();

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'monthlyBreakdown' => $monthlyBreakdown,
        ];
    }

    public function buildPeriodMenuAnalytics(Carbon $start, Carbon $end, bool $limitTopTen = true): array
    {
        $menuStats = $this->buildPeriodMenuStats($start, $end);
        $totalMenuSold = (int) $menuStats->sum('total_qty');

        $contributions = $menuStats
            ->map(function ($item) use ($totalMenuSold) {
                $qty = (int) $item->total_qty;
                $item->contribution = $totalMenuSold > 0
                    ? round(($qty / $totalMenuSold) * 100, 1)
                    : 0;

                return $item;
            })
            ->sortByDesc('contribution');

        if ($limitTopTen) {
            $contributions = $contributions->take(10);
        }

        return [
            'topMenu' => $menuStats->first(),
            'leastMenu' => $menuStats
                ->sortBy([
                    ['total_qty', 'asc'],
                    ['total_sales', 'asc'],
                ])
                ->first(),
            'contributions' => $contributions->values(),
            'totalMenuSold' => $totalMenuSold,
        ];
    }

    private function buildPeriodMenuStats(Carbon $start, Carbon $end)
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($start, $end);

        return TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->leftJoin('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->whereBetween('transactions.created_at', [$startDateTime, $endDateTime])
            ->selectRaw('transaction_details.menu_id, COALESCE(menus.name, ?) as menu_name, SUM(transaction_details.quantity) as total_qty, SUM(transaction_details.subtotal) as total_sales', ['Menu Terhapus'])
            ->groupBy('transaction_details.menu_id', 'menus.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_sales')
            ->get();
    }

    private function toDateTimeRange(Carbon $start, Carbon $end): array
    {
        return [
            $start->copy()->startOfDay()->toDateTimeString(),
            $end->copy()->endOfDay()->toDateTimeString(),
        ];
    }
}
