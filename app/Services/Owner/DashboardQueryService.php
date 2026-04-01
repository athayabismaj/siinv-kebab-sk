<?php

namespace App\Services\Owner;

use App\Models\Ingredient;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    public function buildDashboardData(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $last7Start = now()->subDays(6)->startOfDay();

        $todayAggregate = Transaction::query()
            ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as total_transactions')
            ->first();
        $todayRevenue = (float) ($todayAggregate->total_revenue ?? 0);
        $todayTransactionsCount = (int) ($todayAggregate->total_transactions ?? 0);

        $topMenusToday = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->whereBetween('transactions.created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
            ->selectRaw('menus.id, menus.name, SUM(transaction_details.quantity) as sold_qty')
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        $bestSeller = $topMenusToday->first();

        $lowStockItems = Ingredient::query()
            ->select('id', 'name', 'stock', 'minimum_stock', 'base_unit')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('(stock - minimum_stock) asc')
            ->limit(8)
            ->get()
            ->map(function (Ingredient $ingredient) {
                $stock = (float) $ingredient->stock;
                $statusKey = $stock <= 0 ? 'critical' : 'warning';

                return [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'stock_label' => $this->formatQuantity($stock, (string) $ingredient->base_unit),
                    'status_key' => $statusKey,
                    'status_label' => $statusKey === 'critical' ? 'Habis' : 'Rendah',
                ];
            });

        $latestTransactions = Transaction::query()
            ->select('id', 'transaction_code', 'total_amount', 'created_at')
            ->latest()
            ->limit(10)
            ->get();

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

        return [
            'todayRevenue' => $todayRevenue,
            'todayTransactionsCount' => $todayTransactionsCount,
            'bestSeller' => $bestSeller,
            'topMenusToday' => $topMenusToday,
            'lowStockItems' => $lowStockItems,
            'salesLast7Days' => $salesLast7Days,
            'latestTransactions' => $latestTransactions,
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
