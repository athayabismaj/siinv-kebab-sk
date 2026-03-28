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
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $search = trim((string) $request->input('search', ''));

        $baseQuery = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereDate('stock_logs.created_at', '>=', $dateFrom->toDateString())
            ->whereDate('stock_logs.created_at', '<=', $dateTo->toDateString())
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

        $todayDate = now()->startOfDay();
        $type = request('type', 'daily');

        if ($type === 'yearly') {
            $prevFrom = $dateFrom->copy()->subYear()->startOfYear()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subYear()->endOfYear()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addYear()->startOfYear()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addYear()->endOfYear()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addYear()->startOfYear()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y'); 
            $inputType = 'number';

        } elseif ($type === 'monthly') {
            $prevFrom = $dateFrom->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subMonth()->endOfMonth()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addMonth()->endOfMonth()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addMonth()->startOfMonth()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m'); 
            $inputType = 'month';
        } else {
            $type = 'daily';
            $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
            $prevTo = $prevFrom;
            $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
            $nextTo = $nextFrom;
            $isFuture = $dateFrom->copy()->addDay()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m-d'); 
            $inputType = 'date';
        }

        $today = now()->toDateString();
        $week  = now()->subDays(6)->toDateString();
        $month = now()->startOfMonth()->toDateString();

        return view('admin.reports.usage', compact(
            'usageItems',
            'summary',
            'search',
            'dateFrom',
            'dateTo',
            'type',
            'prevFrom',
            'prevTo',
            'nextFrom',
            'nextTo',
            'isFuture',
            'inputValue',
            'inputType',
            'today',
            'week',
            'month'
        ));
    }

    public function export(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $search = trim((string) $request->input('search', ''));

        $query = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereDate('stock_logs.created_at', '>=', $dateFrom->toDateString())
            ->whereDate('stock_logs.created_at', '<=', $dateTo->toDateString())
            ->selectRaw(
                'ingredients.name as ingredient_name,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                ingredients.base_unit,
                ingredients.display_unit,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit'
            );

        if ($search !== '') {
            $query->where('ingredients.name', 'like', "%{$search}%");
        }

        $rows = $query->orderByDesc('total_quantity')->get();

        $filename = 'laporan-pemakaian-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Laporan Pemakaian Bahan']);
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, [
                'Bahan',
                'Total Pemakaian',
                'Satuan',
                'Frekuensi',
                'Terakhir Digunakan',
            ]);

            foreach ($rows as $item) {
                fputcsv($output, [
                    $item->ingredient_name,
                    (float) $item->total_quantity,
                    $item->base_unit,
                    $item->usage_count,
                    $item->last_used_at,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $today = now()->startOfDay();

        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : ($request->filled('date') ? Carbon::parse($request->input('date'))->startOfDay() : $today);

        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->startOfDay()
            : ($request->filled('date') ? Carbon::parse($request->input('date'))->startOfDay() : $today);

        if ($from->greaterThan($today)) $from = $today;
        if ($to->greaterThan($today))   $to   = $today;
        if ($from->greaterThan($to)) [$from, $to] = [$to, $from];

        return [$from, $to];
    }
}
