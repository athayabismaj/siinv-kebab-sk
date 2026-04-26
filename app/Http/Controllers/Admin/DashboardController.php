<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Support\AdminCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        [$todayStart, $todayEnd, $todayKey] = $this->todayBounds();

        $totalActiveMenus = $this->getTotalActiveMenus();
        $totalIngredients = $this->getTotalIngredients();
        $transactionsTodayCount = $this->getTransactionsTodayCount($todayStart, $todayEnd, $todayKey);
        $lowStockItems = $this->getLowStockItems();
        $recentStockActivities = $this->getRecentStockActivities();
        $topMenusToday = $this->getTopMenusToday($todayStart, $todayEnd, $todayKey);
        $salesLast7Days = $this->getSalesLast7Days();

        return view('admin.panel_admin', compact(
            'totalActiveMenus',
            'totalIngredients',
            'transactionsTodayCount',
            'lowStockItems',
            'recentStockActivities',
            'topMenusToday',
            'salesLast7Days'
        ));
    }

    private function todayBounds(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [$todayStart, $todayEnd, $todayStart->format('Y-m-d')];
    }

    private function getTotalActiveMenus(): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'total_active_menus'),
            now()->addSeconds(120),
            fn () => Menu::query()
                ->where('is_active', true)
                ->count()
        );
    }

    private function getTotalIngredients(): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'total_ingredients'),
            now()->addSeconds(120),
            fn () => Ingredient::query()->count()
        );
    }

    private function getTransactionsTodayCount($todayStart, $todayEnd, string $todayKey): int
    {
        return (int) Cache::remember(
            AdminCache::key('dashboard', 'transactions_today:' . $todayKey),
            now()->addSeconds(90),
            fn () => Transaction::query()
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count()
        );
    }

    private function getLowStockItems()
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
                ->map(fn (Ingredient $ingredient) => $this->mapLowStockItem($ingredient))
        );
    }

    private function getRecentStockActivities()
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'recent_stock_activities'),
            now()->addSeconds(90),
            fn () => StockLog::query()
                ->with('ingredient:id,name,base_unit')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (StockLog $log) => $this->mapStockActivity($log))
        );
    }

    private function getTopMenusToday($todayStart, $todayEnd, string $todayKey)
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'top_menus_today:' . $todayKey),
            now()->addSeconds(90),
            fn () => DB::table('transaction_details')
                ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
                ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
                ->whereBetween('transactions.created_at', [$todayStart, $todayEnd])
                ->selectRaw('menus.id, menus.name, SUM(transaction_details.quantity) as sold_qty')
                ->groupBy('menus.id', 'menus.name')
                ->orderByDesc('sold_qty')
                ->limit(5)
                ->get()
        );
    }

    private function getSalesLast7Days()
    {
        return Cache::remember(
            AdminCache::key('dashboard', 'sales_last_7_days:' . now()->toDateString()),
            now()->addSeconds(90),
            function () {
                $todayEnd = now()->endOfDay();
                $last7Start = now()->subDays(6)->startOfDay();

                $dailySalesRaw = Transaction::query()
                    ->selectRaw('DATE(created_at) as sale_date, COALESCE(SUM(total_amount), 0) as omzet')
                    ->whereBetween('created_at', [$last7Start->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at) asc')
                    ->get()
                    ->keyBy('sale_date');

                $salesLast7Days = collect(range(6, 0))
                    ->map(function (int $dayOffset) use ($dailySalesRaw) {
                        $date = now()->subDays($dayOffset)->toDateString();
                        $omzet = (float) optional($dailySalesRaw->get($date))->omzet;

                        return [
                            'date' => $date,
                            'label' => now()->subDays($dayOffset)->translatedFormat('D, d M'),
                            'is_today' => $dayOffset === 0,
                            'omzet' => $omzet,
                        ];
                    });

                $maxOmzet = (float) $salesLast7Days->max('omzet');
                $salesLast7Days = $salesLast7Days->map(function (array $row) use ($maxOmzet) {
                    $percentage = $maxOmzet > 0 ? ($row['omzet'] / $maxOmzet) * 100 : 0;
                    $row['bar_width'] = max(6, (int) round($percentage));

                    return $row;
                });

                return $salesLast7Days;
            }
        );
    }

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

        $formatted = number_format($displayValue, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        if ($formatted === '') {
            $formatted = '0';
        }

        return trim($formatted . ' ' . $displayUnit);
    }
}


