<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        return $this->daily($request);
    }

    public function daily(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $summary = $this->buildDailySummary($selectedDate);
        $analytics = $this->buildMenuAnalytics($selectedDate);

        return view('owner.reports.sales_daily', [
            'selectedDate' => $selectedDate,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'totalMenuSold' => $summary['totalMenuSold'],
            'topMenu' => $analytics['topMenu'],
            'leastMenu' => $analytics['leastMenu'],
            'contributions' => $analytics['contributions'],
        ]);
    }

    public function monthly(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
        $summary = $this->buildMonthlySummary($selectedMonth);

        return view('owner.reports.sales_monthly', [
            'selectedMonth' => $selectedMonth,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'dailyBreakdown' => $summary['dailyBreakdown'],
        ]);
    }

    public function menuAnalysis(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $analytics = $this->buildMenuAnalytics($selectedDate);

        return view('owner.analytics.menu', [
            'selectedDate' => $selectedDate,
            'topMenu' => $analytics['topMenu'],
            'leastMenu' => $analytics['leastMenu'],
            'contributions' => $analytics['contributions'],
            'totalMenuSold' => $analytics['totalMenuSold'],
        ]);
    }

    public function export(Request $request)
    {
        return $this->exportDaily($request);
    }

    public function exportDaily(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $summary = $this->buildDailySummary($selectedDate);
        $analytics = $this->buildMenuAnalytics($selectedDate, false);

        $filename = 'laporan-penjualan-harian-' . $selectedDate->toDateString() . '.csv';

        return response()->streamDownload(function () use ($selectedDate, $summary, $analytics) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Jenis Laporan', 'Harian']);
            fputcsv($output, ['Tanggal', $selectedDate->format('Y-m-d')]);
            fputcsv($output, ['Total Omzet', (string) $summary['totalRevenue']]);
            fputcsv($output, ['Jumlah Transaksi', (string) $summary['totalTransactions']]);
            fputcsv($output, ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)]);
            fputcsv($output, []);

            fputcsv($output, ['Menu', 'Qty', 'Kontribusi (%)', 'Penjualan']);
            foreach ($analytics['contributions'] as $item) {
                fputcsv($output, [
                    $item->menu_name,
                    (int) $item->total_qty,
                    (float) $item->contribution,
                    (float) $item->total_sales,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportMonthly(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
        $summary = $this->buildMonthlySummary($selectedMonth);

        $filename = 'laporan-penjualan-bulanan-' . $selectedMonth->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($selectedMonth, $summary) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Jenis Laporan', 'Bulanan']);
            fputcsv($output, ['Bulan', $selectedMonth->format('Y-m')]);
            fputcsv($output, ['Total Omzet', (string) $summary['totalRevenue']]);
            fputcsv($output, ['Jumlah Transaksi', (string) $summary['totalTransactions']]);
            fputcsv($output, ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)]);
            fputcsv($output, []);

            fputcsv($output, ['Tanggal', 'Jumlah Transaksi', 'Omzet']);
            foreach ($summary['dailyBreakdown'] as $row) {
                fputcsv($output, [
                    $row->date,
                    (int) $row->trx_count,
                    (float) $row->revenue,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildDailySummary(Carbon $selectedDate): array
    {
        $transactionsQuery = Transaction::query()
            ->whereDate('created_at', '=', $selectedDate->toDateString());

        $totalTransactions = (clone $transactionsQuery)->count();
        $totalRevenue = (float) (clone $transactionsQuery)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $menuStats = $this->buildMenuStats($selectedDate);

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'totalMenuSold' => (int) $menuStats->sum('total_qty'),
        ];
    }

    private function buildMonthlySummary(Carbon $selectedMonth): array
    {
        $query = Transaction::query()
            ->whereYear('created_at', $selectedMonth->year)
            ->whereMonth('created_at', $selectedMonth->month);

        $totalTransactions = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $dailyBreakdown = DB::table('transactions')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, SUM(total_amount) as revenue')
            ->whereYear('created_at', $selectedMonth->year)
            ->whereMonth('created_at', $selectedMonth->month)
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

    private function buildMenuAnalytics(Carbon $selectedDate, bool $limitTopTen = true): array
    {
        $menuStats = $this->buildMenuStats($selectedDate);
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

    private function buildMenuStats(Carbon $selectedDate)
    {
        return TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->leftJoin('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->whereDate('transactions.created_at', '=', $selectedDate->toDateString())
            ->selectRaw('transaction_details.menu_id, COALESCE(menus.name, ?) as menu_name, SUM(transaction_details.quantity) as total_qty, SUM(transaction_details.subtotal) as total_sales', ['Menu Terhapus'])
            ->groupBy('transaction_details.menu_id', 'menus.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_sales')
            ->get();
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

    private function resolveSelectedMonth(string $monthInput): Carbon
    {
        $thisMonth = now()->startOfMonth();

        if ($monthInput === '') {
            return $thisMonth;
        }

        try {
            $date = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
            return $date->greaterThan($thisMonth) ? $thisMonth : $date;
        } catch (\Throwable) {
            return $thisMonth;
        }
    }
}
