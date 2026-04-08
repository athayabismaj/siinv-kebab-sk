<?php

namespace App\Services\Owner;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\AdminCache;
use App\Services\Analytics\DailySalesSummaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesReportQueryService
{
    public function __construct(
        private readonly DailySalesSummaryService $dailySummaryService
    ) {
    }

    public function buildDailySummary(Carbon $selectedDate): array
    {
        $dateKey = $selectedDate->toDateString();

        return $this->remember('daily_summary:' . $dateKey, function () use ($selectedDate) {
            $summary = $this->dailySummaryService->getOrBuildForDate($selectedDate);
            $totalTransactions = (int) $summary['total_transactions'];
            $totalRevenue = (float) $summary['total_revenue'];

            return [
                'totalTransactions' => $totalTransactions,
                'totalRevenue' => $totalRevenue,
                'avgTransaction' => $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0,
                'totalMenuSold' => (int) $summary['total_items_sold'],
            ];
        });
    }

    public function buildMonthlySummary(Carbon $selectedMonth): array
    {
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();
        $monthKey = $selectedMonth->format('Y-m');

        return $this->remember('monthly_summary:' . $monthKey, function () use ($monthStart, $monthEnd) {
            $summary = $this->dailySummaryService->getRange($monthStart, $monthEnd);
            $totalTransactions = (int) $summary['total_transactions'];
            $totalRevenue = (float) $summary['total_revenue'];

            return [
                'totalTransactions' => $totalTransactions,
                'totalRevenue' => $totalRevenue,
                'avgTransaction' => $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0,
                'dailyBreakdown' => $summary['daily_breakdown'],
            ];
        });
    }

    public function buildWeeklySummary(Carbon $weekAnchor): array
    {
        $weekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        $key = $weekStart->toDateString() . ':' . $weekEnd->toDateString();

        return $this->remember('weekly_summary:' . $key, function () use ($weekStart, $weekEnd) {
            $summary = $this->dailySummaryService->getRange($weekStart, $weekEnd);
            $totalTransactions = (int) $summary['total_transactions'];
            $totalRevenue = (float) $summary['total_revenue'];

            return [
                'totalTransactions' => $totalTransactions,
                'totalRevenue' => $totalRevenue,
                'avgTransaction' => $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0,
                'weeklyBreakdown' => $summary['daily_breakdown'],
            ];
        });
    }

    public function buildYearlySummary(int $year): array
    {
        return $this->remember('yearly_summary:' . $year, function () use ($year) {
            $start = Carbon::create($year, 1, 1)->startOfMonth();
            $end = Carbon::create($year, 12, 31)->endOfMonth();

            $summary = $this->dailySummaryService->getRange($start, $end);
            $totalTransactions = (int) $summary['total_transactions'];
            $totalRevenue = (float) $summary['total_revenue'];

            $monthlyBreakdown = collect($summary['daily_breakdown'])
                ->groupBy(function ($row) {
                    return (int) Carbon::parse($row->date)->month;
                })
                ->map(function ($rows, $month) {
                    return (object) [
                        'month' => (int) $month,
                        'trx_count' => (int) collect($rows)->sum('trx_count'),
                        'revenue' => (float) collect($rows)->sum('revenue'),
                    ];
                })
                ->sortBy('month')
                ->values();

            return [
                'totalTransactions' => $totalTransactions,
                'totalRevenue' => $totalRevenue,
                'avgTransaction' => $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0,
                'monthlyBreakdown' => $monthlyBreakdown,
            ];
        });
    }

    public function buildPeriodMenuAnalytics(Carbon $start, Carbon $end, bool $limitTopTen = true): array
    {
        $cacheSuffix = 'menu_analytics:' . $start->toDateString() . ':' . $end->toDateString() . ':' . ($limitTopTen ? '10' : 'all');

        return $this->remember($cacheSuffix, function () use ($start, $end, $limitTopTen) {
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
        });
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

    private function remember(string $suffix, callable $resolver): array
    {
        return Cache::remember(
            AdminCache::key('cashflow', 'owner:' . $suffix),
            now()->addSeconds(120),
            $resolver
        );
    }
}
