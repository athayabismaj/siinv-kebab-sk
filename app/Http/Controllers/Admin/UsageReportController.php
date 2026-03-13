<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageReportController extends Controller
{
    public function index(Request $request)
    {
        $dateInput = (string) $request->input('date', '');
        $search = trim((string) $request->input('search', ''));
        $selectedDate = $this->resolveSelectedDate($dateInput);

        $baseQuery = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereDate('stock_logs.created_at', '=', $selectedDate->toDateString())
            ->selectRaw(
                'stock_logs.ingredient_id,
                ingredients.name as ingredient_name,
                ingredients.base_unit,
                ingredients.display_unit,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'stock_logs.ingredient_id',
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit'
            );

        if ($search !== '') {
            $baseQuery->where('ingredients.name', 'like', "%{$search}%");
        }

        $usageItems = (clone $baseQuery)
            ->orderByDesc(DB::raw('SUM(ABS(stock_logs.quantity))'))
            ->paginate(10)
            ->withQueryString();

        $totalsBase = (clone $baseQuery)->get();

        $summary = [
            'ingredients_count' => $totalsBase->count(),
            'logs_count' => (int) $totalsBase->sum('usage_count'),
            'total_base_quantity' => (float) $totalsBase->sum('total_quantity'),
        ];

        return view('admin.reports.usage', compact(
            'usageItems',
            'summary',
            'search',
            'selectedDate'
        ));
    }

    private function resolveSelectedDate(string $dateInput): Carbon
    {
        $today = now()->startOfDay();

        if ($dateInput === '') {
            return $today;
        }

        try {
            $date = Carbon::parse($dateInput)->startOfDay();

            return $date->greaterThan($today) ? $today : $date;
        } catch (\Throwable) {
            return $today;
        }
    }
}
