<?php

namespace App\Services\Admin;

use App\Models\CashflowEntry;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Support\AdminCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $today, ?int $branchId): array
    {
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $todayKey = $todayStart->toDateString();
        $overview = $this->overviewMetrics($todayStart, $todayEnd, $todayKey, $branchId);

        return [
            'totalActiveMenus' => $overview['totalActiveMenus'],
            'totalIngredients' => $overview['totalIngredients'],
            'transactionsTodayCount' => $overview['transactionsTodayCount'],
            'lowStockItems' => $this->lowStockItems(),
            'lowStockSummary' => $overview['lowStockSummary'],
            'recentStockActivities' => $this->recentStockActivities($todayStart, $todayEnd, $todayKey, $branchId),
            'topMenusToday' => $this->topMenusToday($todayStart, $todayEnd, $todayKey, $branchId),
            'salesLast7Days' => $this->salesLast7Days($today, $branchId),
            'expenseToday' => $overview['expenseToday'],
            'dailyStockStatus' => $overview['dailyStockStatus'],
        ];
    }

    /**
     * Gabungkan metrik skalar menjadi satu statement SQL. Pada database remote,
     * satu query sedikit lebih kompleks jauh lebih murah daripada banyak round-trip.
     *
     * @return array<string, mixed>
     */
    private function overviewMetrics(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): array
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'overview:'.$dateKey.':'.($branchId ?? 'all')),
            now()->addMinutes(5),
            function () use ($start, $end, $dateKey, $branchId): array {
                $transactions = Transaction::query()
                    ->successful()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereBetween('created_at', [$start, $end]);

                $expenses = CashflowEntry::query()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereDate('entry_date', $dateKey)
                    ->where('type', 'expense');

                $sessions = DailyStockSession::query()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereDate('session_date', $dateKey);

                $row = DB::query()
                    ->selectSub(
                        Menu::query()->selectRaw('COUNT(*)')->where('is_active', true),
                        'total_active_menus'
                    )
                    ->selectSub(
                        Ingredient::query()->selectRaw('COUNT(*)'),
                        'total_ingredients'
                    )
                    ->selectSub(
                        (clone $transactions)->selectRaw('COUNT(*)'),
                        'transactions_today_count'
                    )
                    ->selectSub(
                        Ingredient::query()
                            ->whereColumn('stock', '<=', 'minimum_stock')
                            ->selectRaw('COUNT(*)'),
                        'total_low'
                    )
                    ->selectSub(
                        Ingredient::query()
                            ->where('stock', '<=', 0)
                            ->selectRaw('COUNT(*)'),
                        'critical_count'
                    )
                    ->selectSub(
                        Ingredient::query()
                            ->where('stock', '>', 0)
                            ->whereColumn('stock', '<=', 'minimum_stock')
                            ->selectRaw('COUNT(*)'),
                        'warning_count'
                    )
                    ->selectSub(
                        (clone $expenses)->selectRaw('COALESCE(SUM(amount), 0)'),
                        'expense_total'
                    )
                    ->selectSub(
                        (clone $expenses)->selectRaw('COUNT(*)'),
                        'expense_count'
                    )
                    ->selectSub(
                        (clone $sessions)->selectRaw('COUNT(*)'),
                        'total_sessions'
                    )
                    ->selectSub(
                        (clone $sessions)
                            ->whereRaw("LOWER(TRIM(status)) = 'open'")
                            ->selectRaw('COUNT(*)'),
                        'open_sessions'
                    )
                    ->selectSub(
                        (clone $sessions)
                            ->whereRaw("LOWER(TRIM(status)) = 'closed'")
                            ->selectRaw('COUNT(*)'),
                        'closed_sessions'
                    )
                    ->first();

                $totalSessions = (int) ($row->total_sessions ?? 0);
                $openSessions = (int) ($row->open_sessions ?? 0);
                $closedSessions = (int) ($row->closed_sessions ?? 0);

                $dailyStockStatus = match (true) {
                    $totalSessions === 0 => [
                        'key' => 'not_opened',
                        'label' => 'Belum Dibuka',
                        'description' => 'Belum ada sesi stok harian yang dibuka.',
                    ],
                    $openSessions > 0 => [
                        'key' => 'open',
                        'label' => 'Masih Berjalan',
                        'description' => "{$openSessions} dari {$totalSessions} sesi masih aktif.",
                    ],
                    default => [
                        'key' => 'closed',
                        'label' => 'Sudah Ditutup',
                        'description' => "{$closedSessions} sesi stok sudah selesai.",
                    ],
                };

                $dailyStockStatus = array_merge($dailyStockStatus, [
                    'total_sessions' => $totalSessions,
                    'open_sessions' => $openSessions,
                    'closed_sessions' => $closedSessions,
                ]);

                return [
                    'totalActiveMenus' => (int) ($row->total_active_menus ?? 0),
                    'totalIngredients' => (int) ($row->total_ingredients ?? 0),
                    'transactionsTodayCount' => (int) ($row->transactions_today_count ?? 0),
                    'lowStockSummary' => [
                        'total_low' => (int) ($row->total_low ?? 0),
                        'critical_count' => (int) ($row->critical_count ?? 0),
                        'warning_count' => (int) ($row->warning_count ?? 0),
                    ],
                    'expenseToday' => [
                        'total' => (float) ($row->expense_total ?? 0),
                        'count' => (int) ($row->expense_count ?? 0),
                    ],
                    'dailyStockStatus' => $dailyStockStatus,
                ];
            },
        );
    }

    private function lowStockItems(): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'low_stock_items'),
            now()->addMinutes(5),
            fn () => Ingredient::query()
                ->select('id', 'name', 'stock', 'minimum_stock', 'base_unit')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderByRaw('(stock - minimum_stock) asc')
                ->limit(8)
                ->get()
                ->map(fn (Ingredient $ingredient) => $this->mapLowStockItem($ingredient)),
        );
    }

    private function recentStockActivities(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'stock_activities_today:'.$dateKey.':'.($branchId ?? 'all')),
            now()->addMinutes(5),
            fn () => StockLog::query()
                ->leftJoin('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
                ->select([
                    'stock_logs.created_at',
                    'stock_logs.type',
                    'stock_logs.quantity',
                    'ingredients.name as ingredient_name',
                    'ingredients.base_unit as ingredient_base_unit',
                ])
                ->when($branchId, fn ($query) => $query->where('stock_logs.branch_id', $branchId))
                ->whereBetween('stock_logs.created_at', [$start, $end])
                ->latest('stock_logs.created_at')
                ->limit(10)
                ->get()
                ->map(fn (StockLog $log) => $this->mapStockActivity($log)),
        );
    }

    private function topMenusToday(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'top_menus_today:success:'.$dateKey.':'.($branchId ?? 'all')),
            now()->addMinutes(5),
            fn () => DB::table('transaction_details')
                ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
                ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
                ->when($branchId, fn ($query) => $query->where('transactions.branch_id', $branchId))
                ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
                ->whereBetween('transactions.created_at', [$start, $end])
                ->selectRaw('menus.id, menus.name, SUM(transaction_details.quantity) as sold_qty')
                ->groupBy('menus.id', 'menus.name')
                ->orderByDesc('sold_qty')
                ->limit(5)
                ->get(),
        );
    }

    private function salesLast7Days(Carbon $today, ?int $branchId): Collection
    {
        $todayKey = $today->toDateString();

        return Cache::remember(
            AdminCache::key('dashboard', 'sales_last_7_days:success:'.$todayKey.':'.($branchId ?? 'all')),
            now()->addMinutes(5),
            function () use ($today, $branchId): Collection {
                $todayEnd = $today->copy()->endOfDay();
                $last7Start = $today->copy()->subDays(6)->startOfDay();

                $dailySalesRaw = Transaction::query()
                    ->successful()
                    ->selectRaw('DATE(created_at) as sale_date, COALESCE(SUM(total_amount), 0) as omzet')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereBetween('created_at', [$last7Start, $todayEnd])
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at) asc')
                    ->get()
                    ->keyBy('sale_date');

                $salesLast7Days = collect(range(6, 0))
                    ->map(function (int $dayOffset) use ($dailySalesRaw, $today): array {
                        $date = $today->copy()->subDays($dayOffset);
                        $omzet = (float) optional($dailySalesRaw->get($date->toDateString()))->omzet;

                        return [
                            'date' => $date->toDateString(),
                            'label' => $date->translatedFormat('D, d M'),
                            'is_today' => $dayOffset === 0,
                            'omzet' => $omzet,
                        ];
                    });

                $maxOmzet = (float) $salesLast7Days->max('omzet');

                return $salesLast7Days->map(function (array $row) use ($maxOmzet): array {
                    $percentage = $maxOmzet > 0 ? ($row['omzet'] / $maxOmzet) * 100 : 0;
                    $row['bar_width'] = max(6, (int) round($percentage));

                    return $row;
                });
            },
        );
    }

    /**
     * @return array<string, int|float|string>
     */
    private function mapLowStockItem(Ingredient $ingredient): array
    {
        $stock = (float) $ingredient->stock;
        $minimum = (float) $ingredient->minimum_stock;
        $statusKey = $stock <= 0 ? 'critical' : 'warning';

        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'stock_label' => $this->formatQuantity($stock, (string) $ingredient->base_unit),
            'minimum_label' => $this->formatQuantity($minimum, (string) $ingredient->base_unit),
            'status_key' => $statusKey,
            'status_label' => $statusKey === 'critical' ? 'Habis' : 'Rendah',
            'stock_value' => $stock,
            'minimum_value' => $minimum,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapStockActivity(StockLog $log): array
    {
        $baseUnit = (string) $log->ingredient_base_unit;
        $qty = (float) $log->quantity;

        if ($log->type === 'in') {
            $activity = 'Restok';
            $quantityLabel = '+'.$this->formatQuantity($qty, $baseUnit);
        } elseif ($log->type === 'out') {
            $activity = 'Pemakaian';
            $quantityLabel = '-'.$this->formatQuantity(abs($qty), $baseUnit);
        } else {
            $activity = 'Penyesuaian';
            $quantityLabel = ($qty >= 0 ? '+' : '-').$this->formatQuantity(abs($qty), $baseUnit);
        }

        return [
            'time' => $log->created_at,
            'ingredient_name' => $log->ingredient_name ?? '-',
            'activity' => $activity,
            'quantity_label' => $quantityLabel,
        ];
    }

    private function formatQuantity(float $value, string $baseUnit): string
    {
        $unit = strtolower(trim($baseUnit));
        $displayValue = $value;
        $displayUnit = $unit;

        if (in_array($unit, ['g', 'gr', 'gram', 'grams'], true)) {
            if ($value >= 1000) {
                $displayValue = $value / 1000;
                $displayUnit = 'kg';
            } else {
                $displayUnit = 'g';
            }
        } elseif (in_array($unit, ['ml', 'milliliter', 'milliliters'], true)) {
            if ($value >= 1000) {
                $displayValue = $value / 1000;
                $displayUnit = 'l';
            } else {
                $displayUnit = 'ml';
            }
        }

        $formatted = rtrim(rtrim(number_format($displayValue, 2, '.', ''), '0'), '.');

        return trim(($formatted === '' ? '0' : $formatted).' '.$displayUnit);
    }
}
