<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\StockLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalActiveMenus = Menu::query()
            ->where('is_active', true)
            ->count();

        $totalIngredients = Ingredient::query()->count();

        $transactionsTodayCount = Transaction::query()
            ->whereDate('created_at', $today)
            ->count();

        $lowStockItems = Ingredient::query()
            ->select('id', 'name', 'stock', 'minimum_stock', 'base_unit')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('(stock - minimum_stock) asc')
            ->limit(8)
            ->get()
            ->map(function (Ingredient $ingredient) {
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
            });

        $recentStockActivities = StockLog::query()
            ->with('ingredient:id,name,base_unit')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (StockLog $log) {
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
            });

        $topMenusToday = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->whereDate('transactions.created_at', $today)
            ->selectRaw('menus.id, menus.name, SUM(transaction_details.quantity) as sold_qty')
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        return view('admin.panel_admin', compact(
            'totalActiveMenus',
            'totalIngredients',
            'transactionsTodayCount',
            'lowStockItems',
            'recentStockActivities',
            'topMenusToday'
        ));
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
