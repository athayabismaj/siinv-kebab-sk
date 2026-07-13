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
use Illuminate\Support\Facades\Schema;

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

        return [
            'totalActiveMenus' => $this->totalActiveMenus(),
            'totalIngredients' => $this->totalIngredients(),
            'transactionsTodayCount' => $this->transactionsTodayCount($todayStart, $todayEnd, $todayKey, $branchId),
            'lowStockItems' => $this->lowStockItems(),
            'lowStockSummary' => $this->lowStockSummary(),
            'recentStockActivities' => $this->recentStockActivities($todayStart, $todayEnd, $todayKey, $branchId),
            'topMenusToday' => $this->topMenusToday($todayStart, $todayEnd, $todayKey, $branchId),
            'salesLast7Days' => $this->salesLast7Days($today, $branchId),
            'expenseToday' => $this->expenseToday($todayKey, $branchId),
            'dailyStockStatus' => $this->dailyStockStatus($todayKey, $branchId),
        ];
    }

    private function totalActiveMenus(): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'total_active_menus'),
            now()->addSeconds(120),
            fn () => Menu::query()->where('is_active', true)->count(),
        );
    }

    private function totalIngredients(): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'total_ingredients'),
            now()->addSeconds(120),
            fn () => Ingredient::query()->count(),
        );
    }

    private function transactionsTodayCount(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'transactions_today:success:' . $dateKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(90),
            fn () => Transaction::query()
                ->successful()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereBetween('created_at', [$start, $end])
                ->count(),
        );
    }

    private function lowStockItems(): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'low_stock_items'),
            now()->addSeconds(90),
            fn () => Ingredient::query()
                ->select('id', 'name', 'stock', 'minimum_stock', 'base_unit')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderByRaw('(stock - minimum_stock) asc')
                ->limit(8)
                ->get()
                ->map(fn (Ingredient $ingredient) => $this->mapLowStockItem($ingredient)),
        );
    }

    /**
     * @return array{total_low:int,critical_count:int,warning_count:int}
     */
    private function lowStockSummary(): array
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'low_stock_summary'),
            now()->addSeconds(90),
            function (): array {
                $summary = Ingredient::query()
                    ->whereColumn('stock', '<=', 'minimum_stock')
                    ->selectRaw(
                        'COUNT(*) as total_low,
                        SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as critical_count,
                        SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as warning_count'
                    )
                    ->first();

                return [
                    'total_low' => (int) ($summary->total_low ?? 0),
                    'critical_count' => (int) ($summary->critical_count ?? 0),
                    'warning_count' => (int) ($summary->warning_count ?? 0),
                ];
            },
        );
    }

    private function recentStockActivities(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'stock_activities_today:' . $dateKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(90),
            fn () => StockLog::query()
                ->with('ingredient:id,name,base_unit')
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (StockLog $log) => $this->mapStockActivity($log)),
        );
    }

    private function topMenusToday(Carbon $start, Carbon $end, string $dateKey, ?int $branchId): Collection
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'top_menus_today:success:' . $dateKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(90),
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
            AdminCache::key('dashboard', 'sales_last_7_days:success:' . $todayKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(90),
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
     * @return array{total:float,count:int}
     */
    private function expenseToday(string $dateKey, ?int $branchId): array
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'expense_today:' . $dateKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(90),
            function () use ($dateKey, $branchId): array {
                if (! Schema::hasTable('cashflow_entries')) {
                    return ['total' => 0.0, 'count' => 0];
                }

                $aggregate = CashflowEntry::query()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereDate('entry_date', $dateKey)
                    ->where('type', 'expense')
                    ->selectRaw('COALESCE(SUM(amount), 0) as expense_total, COUNT(*) as expense_count')
                    ->first();

                return [
                    'total' => (float) ($aggregate->expense_total ?? 0),
                    'count' => (int) ($aggregate->expense_count ?? 0),
                ];
            },
        );
    }

    /**
     * @return array{key:string,label:string,description:string,total_sessions:int,open_sessions:int,closed_sessions:int}
     */
    private function dailyStockStatus(string $dateKey, ?int $branchId): array
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'daily_stock_status:' . $dateKey . ':' . ($branchId ?? 'all')),
            now()->addSeconds(60),
            function () use ($dateKey, $branchId): array {
                if (! Schema::hasTable('daily_stock_sessions')) {
                    return [
                        'key' => 'not_opened',
                        'label' => 'Belum Dibuka',
                        'description' => 'Tabel sesi stok harian belum tersedia.',
                        'total_sessions' => 0,
                        'open_sessions' => 0,
                        'closed_sessions' => 0,
                    ];
                }

                $aggregate = DailyStockSession::query()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereDate('session_date', $dateKey)
                    ->selectRaw(
                        "COUNT(*) as total_sessions,
                        SUM(CASE WHEN LOWER(TRIM(status)) = 'open' THEN 1 ELSE 0 END) as open_sessions,
                        SUM(CASE WHEN LOWER(TRIM(status)) = 'closed' THEN 1 ELSE 0 END) as closed_sessions"
                    )
                    ->first();

                $totalSessions = (int) ($aggregate->total_sessions ?? 0);
                $openSessions = (int) ($aggregate->open_sessions ?? 0);
                $closedSessions = (int) ($aggregate->closed_sessions ?? 0);

                $status = match (true) {
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

                return array_merge($status, [
                    'total_sessions' => $totalSessions,
                    'open_sessions' => $openSessions,
                    'closed_sessions' => $closedSessions,
                ]);
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
        $ingredient = $log->ingredient;
        $baseUnit = (string) optional($ingredient)->base_unit;
        $qty = (float) $log->quantity;

        if ($log->type === 'in') {
            $activity = 'Restok';
            $quantityLabel = '+' . $this->formatQuantity($qty, $baseUnit);
        } elseif ($log->type === 'out') {
            $activity = 'Pemakaian';
            $quantityLabel = '-' . $this->formatQuantity(abs($qty), $baseUnit);
        } else {
            $activity = 'Penyesuaian';
            $quantityLabel = ($qty >= 0 ? '+' : '-') . $this->formatQuantity(abs($qty), $baseUnit);
        }

        return [
            'time' => $log->created_at,
            'ingredient_name' => optional($ingredient)->name ?? '-',
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

        return trim(($formatted === '' ? '0' : $formatted) . ' ' . $displayUnit);
    }
}
