<?php

namespace App\Services\Owner;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\AdminCache;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesReportQueryService
{
    public function buildDailySummary(Carbon $selectedDate, ?int $branchId = null): array
    {
        $dateKey = $selectedDate->toDateString();

        return $this->remember('daily_summary_success:' . $dateKey . ':branch:' . ($branchId ?: 'all'), fn () => $this->buildSuccessfulSummary(
            $selectedDate,
            $selectedDate,
            $branchId
        ));
    }

    public function buildMonthlySummary(Carbon $selectedMonth, bool $bypassCache = false, ?int $branchId = null): array
    {
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();
        $monthKey = $selectedMonth->format('Y-m');

        $resolver = function () use ($monthStart, $monthEnd, $branchId) {
            return [
                ...$this->buildSuccessfulSummary($monthStart, $monthEnd, $branchId),
                'dailyBreakdown' => $this->buildSuccessfulDailyBreakdown($monthStart, $monthEnd, $branchId),
            ];
        };

        return $bypassCache ? $resolver() : $this->remember('monthly_summary_success:' . $monthKey . ':branch:' . ($branchId ?: 'all'), $resolver);
    }

    public function buildWeeklySummary(Carbon $weekAnchor, bool $bypassCache = false, ?int $branchId = null): array
    {
        $weekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        $key = $weekStart->toDateString() . ':' . $weekEnd->toDateString();

        $resolver = function () use ($weekStart, $weekEnd, $branchId) {
            return [
                ...$this->buildSuccessfulSummary($weekStart, $weekEnd, $branchId),
                'weeklyBreakdown' => $this->buildSuccessfulDailyBreakdown($weekStart, $weekEnd, $branchId),
            ];
        };

        return $bypassCache ? $resolver() : $this->remember('weekly_summary_success:' . $key . ':branch:' . ($branchId ?: 'all'), $resolver);
    }

    public function buildYearlySummary(int $year, bool $bypassCache = false, ?int $branchId = null): array
    {
        $resolver = function () use ($year, $branchId) {
            $start = Carbon::create($year, 1, 1)->startOfMonth();
            $end = Carbon::create($year, 12, 31)->endOfMonth();

            $summary = $this->buildSuccessfulSummary($start, $end, $branchId);

            $monthlyBreakdown = $this->buildSuccessfulDailyBreakdown($start, $end, $branchId)
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
                'totalTransactions' => $summary['totalTransactions'],
                'totalRevenue' => $summary['totalRevenue'],
                'avgTransaction' => $summary['avgTransaction'],
                'monthlyBreakdown' => $monthlyBreakdown,
            ];
        };

        return $bypassCache ? $resolver() : $this->remember('yearly_summary_success:' . $year . ':branch:' . ($branchId ?: 'all'), $resolver);
    }

    public function buildPeriodMenuAnalytics(Carbon $start, Carbon $end, bool $limitTopTen = true, ?int $branchId = null): array
    {
        $cacheSuffix = 'menu_analytics_success:' . $start->toDateString() . ':' . $end->toDateString() . ':' . ($limitTopTen ? '10' : 'all') . ':branch:' . ($branchId ?: 'all');

        return $this->remember($cacheSuffix, function () use ($start, $end, $limitTopTen, $branchId) {
            $menuStats = $this->buildPeriodMenuStats($start, $end, $branchId);
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

    public function buildPeriodTransactionOverview(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        $cacheSuffix = 'transaction_overview_cash:' . $start->toDateString() . ':' . $end->toDateString() . ':branch:' . ($branchId ?: 'all');

        return $this->remember($cacheSuffix, function () use ($start, $end, $branchId) {
            [$startDateTime, $endDateTime] = $this->toDateTimeRange($start, $end);

            $paymentRows = Transaction::query()
                ->leftJoin('payment_methods', 'payment_methods.id', '=', 'transactions.payment_method_id')
                ->successful()
                ->whereBetween('transactions.created_at', [$startDateTime, $endDateTime])
                ->selectRaw('
                    payment_methods.id as payment_method_id,
                    COALESCE(payment_methods.name, ?) as payment_method_name,
                    COUNT(transactions.id) as trx_count,
                    COALESCE(SUM(transactions.total_amount), 0) as total_amount
                ', ['Tanpa Metode'])
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->orderByDesc('total_amount');

            BranchScope::apply($paymentRows, $branchId, 'transactions.branch_id');

            $paymentRows = $paymentRows->get();

            $cashTotal = 0.0;

            foreach ($paymentRows as $row) {
                $name = strtolower((string) $row->payment_method_name);

                if (str_contains($name, 'cash') || str_contains($name, 'tunai')) {
                    $cashTotal += (float) $row->total_amount;
                }
            }

            $successQuery = Transaction::query()
                ->successful()
                ->whereBetween('created_at', [$startDateTime, $endDateTime]);
            BranchScope::apply($successQuery, $branchId, 'branch_id');
            $successCount = (int) $successQuery->count();

            $canceledQuery = Transaction::query()
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->whereRaw('LOWER(status) = ?', ['void']);
            BranchScope::apply($canceledQuery, $branchId, 'branch_id');
            $canceledCount = (int) $canceledQuery->count();

            $canceledTotalQuery = Transaction::query()
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->whereRaw('LOWER(status) = ?', ['void']);
            BranchScope::apply($canceledTotalQuery, $branchId, 'branch_id');
            $canceledTotal = (float) $canceledTotalQuery->sum('total_amount');

            $transactions = DB::table('transactions')
                ->leftJoin('users', 'users.id', '=', 'transactions.user_id')
                ->leftJoin('payment_methods', 'payment_methods.id', '=', 'transactions.payment_method_id')
                ->leftJoin('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->whereBetween('transactions.created_at', [$startDateTime, $endDateTime])
                ->selectRaw('
                    transactions.id,
                    transactions.transaction_code,
                    transactions.created_at,
                    transactions.total_amount,
                    transactions.status,
                    users.name as cashier_name,
                    payment_methods.name as payment_method_name,
                    COALESCE(SUM(transaction_details.quantity), 0) as item_count
                ')
                ->groupBy(
                    'transactions.id',
                    'transactions.transaction_code',
                    'transactions.created_at',
                    'transactions.total_amount',
                    'transactions.status',
                    'users.name',
                    'payment_methods.name'
                )
                ->orderByDesc('transactions.created_at');

            BranchScope::apply($transactions, $branchId, 'transactions.branch_id');
            $transactions = $transactions->get();

            return [
                'paymentSummary' => [
                    'cashTotal' => $cashTotal,
                    'successCount' => $successCount,
                    'canceledCount' => $canceledCount,
                    'canceledTotal' => $canceledTotal,
                    'methods' => $paymentRows,
                ],
                'salesTransactions' => $transactions,
            ];
        });
    }

    private function buildPeriodMenuStats(Carbon $start, Carbon $end, ?int $branchId = null)
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($start, $end);

        $query = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->leftJoin('menu_variants', 'menu_variants.id', '=', 'transaction_details.menu_variant_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menus.category_id')
            ->whereBetween('transactions.created_at', [$startDateTime, $endDateTime])
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->where(function ($query) {
                $query->whereNull('menu_categories.id')
                    ->orWhere('menu_categories.is_addon', false);
            })
            ->selectRaw('
                menus.id as menu_id,
                menus.name as base_menu_name,
                menu_variants.id as menu_variant_id,
                menu_variants.name as variant_name,
                SUM(transaction_details.quantity) as total_qty,
                SUM(transaction_details.subtotal) as total_sales
            ')
            ->groupBy('menus.id', 'menus.name', 'menu_variants.id', 'menu_variants.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_sales');

        BranchScope::apply($query, $branchId, 'transactions.branch_id');

        return $query->get()
            ->map(function ($item) {
                $item->menu_name = $this->formatVariantLabel(
                    (string) $item->base_menu_name,
                    $item->variant_name === null ? null : (string) $item->variant_name
                );

                return $item;
            });
    }

    private function buildSuccessfulSummary(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($start, $end);

        $aggregateQuery = Transaction::query()
            ->successful()
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue');

        BranchScope::apply($aggregateQuery, $branchId, 'branch_id');
        $aggregate = $aggregateQuery->first();

        $totalTransactions = (int) ($aggregate->total_transactions ?? 0);
        $totalRevenue = (float) ($aggregate->total_revenue ?? 0);

        $totalMenuSoldQuery = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menus.category_id')
            ->whereBetween('transactions.created_at', [$startDateTime, $endDateTime])
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->where(function ($query) {
                $query->whereNull('menu_categories.id')
                    ->orWhere('menu_categories.is_addon', false);
            });

        BranchScope::apply($totalMenuSoldQuery, $branchId, 'transactions.branch_id');
        $totalMenuSold = (int) $totalMenuSoldQuery->sum('transaction_details.quantity');

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0,
            'totalMenuSold' => $totalMenuSold,
        ];
    }

    private function buildSuccessfulDailyBreakdown(Carbon $start, Carbon $end, ?int $branchId = null)
    {
        [$startDateTime, $endDateTime] = $this->toDateTimeRange($start, $end);

        $query = Transaction::query()
            ->successful()
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date');

        BranchScope::apply($query, $branchId, 'branch_id');

        return $query->get()
            ->map(function ($row) {
                return (object) [
                    'date' => (string) $row->date,
                    'trx_count' => (int) $row->trx_count,
                    'revenue' => (float) $row->revenue,
                ];
            })
            ->values();
    }

    private function formatVariantLabel(string $menuName, ?string $variantName): string
    {
        $menuName = trim($menuName);
        $variantName = trim((string) $variantName);

        if ($variantName === '') {
            return $menuName;
        }

        if (str_starts_with(strtolower($variantName), strtolower($menuName))) {
            return $variantName;
        }

        return trim($menuName . ' ' . $variantName);
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
            AdminCache::key('transactions', 'owner:sales:' . $suffix),
            now()->addSeconds(120),
            $resolver
        );
    }
}
