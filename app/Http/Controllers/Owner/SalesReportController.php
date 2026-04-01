<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\SalesReportQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function __construct(
        private readonly SalesReportQueryService $queryService
    ) {}

    public function index(Request $request)
    {
        $type = $request->input('type', 'daily');
        if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) {
            $type = 'daily';
        }
        $data = ['type' => $type];

        switch ($type) {
            case 'weekly':
                $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
                $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
                $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
                $summary = $this->queryService->buildWeeklySummary($weekAnchor);
                $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd);
                $data = array_merge($data, [
                    'selectedWeekStart' => $selectedWeekStart,
                    'selectedWeekEnd' => $selectedWeekEnd,
                    'totalRevenue' => $summary['totalRevenue'],
                    'totalTransactions' => $summary['totalTransactions'],
                    'avgTransaction' => $summary['avgTransaction'],
                    'weeklyBreakdown' => $summary['weeklyBreakdown'],
                    'topMenu' => $analytics['topMenu'],
                    'leastMenu' => $analytics['leastMenu'],
                    'contributions' => $analytics['contributions'],
                    'totalMenuSold' => $analytics['totalMenuSold'],
                ]);
                break;

            case 'monthly':
                $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
                $summary = $this->queryService->buildMonthlySummary($selectedMonth);
                $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedMonth->startOfMonth(), $selectedMonth->copy()->endOfMonth());
                $data = array_merge($data, [
                    'selectedMonth' => $selectedMonth,
                    'totalRevenue' => $summary['totalRevenue'],
                    'totalTransactions' => $summary['totalTransactions'],
                    'avgTransaction' => $summary['avgTransaction'],
                    'dailyBreakdown' => $summary['dailyBreakdown'],
                    'topMenu' => $analytics['topMenu'],
                    'leastMenu' => $analytics['leastMenu'],
                    'contributions' => $analytics['contributions'],
                    'totalMenuSold' => $analytics['totalMenuSold'],
                ]);
                break;

            case 'daily':
            default:
                $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
                $summary = $this->queryService->buildDailySummary($selectedDate);
                $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate);
                $data = array_merge($data, [
                    'selectedDate' => $selectedDate,
                    'totalRevenue' => $summary['totalRevenue'],
                    'totalTransactions' => $summary['totalTransactions'],
                    'avgTransaction' => $summary['avgTransaction'],
                    'totalMenuSold' => $summary['totalMenuSold'],
                    'topMenu' => $analytics['topMenu'],
                    'leastMenu' => $analytics['leastMenu'],
                    'contributions' => $analytics['contributions'],
                ]);
                break;
        }

        return view('owner.reports.sales_unified', $data);
    }

    public function closingIndex(Request $request)
    {
        $closings = \App\Models\PeriodClosing::with('closedBy')
            ->orderByDesc('period_date')
            ->paginate(12);

        // Preview data untuk bulan ini (jika belum tutup buku)
        $thisMonth = now()->startOfMonth();
        $isClosed = \App\Models\PeriodClosing::where('period_type', 'monthly')
            ->where('period_date', $thisMonth->toDateString())
            ->exists();

        $preview = null;
        if (!$isClosed) {
            $preview = $this->queryService->buildMonthlySummary($thisMonth);
        }

        return view('owner.reports.closing_index', [
            'closings' => $closings,
            'preview' => $preview,
            'isClosed' => $isClosed,
            'thisMonth' => $thisMonth,
        ]);
    }

    public function closePeriod(Request $request)
    {
        $request->validate([
            'period_type' => 'required|in:monthly,yearly',
            'period_date' => 'required|date',
        ]);

        $date = Carbon::parse($request->period_date)->startOfDay();
        
        // Cek apakah sudah ditutup
        if (\App\Models\PeriodClosing::where('period_type', $request->period_type)->where('period_date', $date->toDateString())->exists()) {
            return back()->with('error', 'Periode ini sudah ditutup sebelumnya.');
        }

        // Hitung data final
        $summary = $request->period_type === 'monthly' 
            ? $this->queryService->buildMonthlySummary($date)
            : $this->queryService->buildYearlySummary($date->year);

        \App\Models\PeriodClosing::create([
            'period_type' => $request->period_type,
            'period_date' => $date->toDateString(),
            'total_revenue' => $summary['totalRevenue'],
            'total_transactions' => $summary['totalTransactions'],
            'closed_by_user_id' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('owner.reports.closing.index')->with('success', 'Tutup buku periode ' . $date->format('M Y') . ' berhasil!');
    }

    public function menuAnalysis(Request $request)
    {
        $type = $request->input('type', 'daily');
        if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) {
            $type = 'daily';
        }

        $data = ['type' => $type];

        if ($type === 'weekly') {
            $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
            $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
            $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd, false);

            $data = array_merge($data, [
                'selectedWeekStart' => $selectedWeekStart,
                'selectedWeekEnd' => $selectedWeekEnd,
                'topMenu' => $analytics['topMenu'],
                'leastMenu' => $analytics['leastMenu'],
                'contributions' => $analytics['contributions'],
                'totalMenuSold' => $analytics['totalMenuSold'],
            ]);
        } elseif ($type === 'monthly') {
            $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics(
                $selectedMonth->copy()->startOfMonth(),
                $selectedMonth->copy()->endOfMonth(),
                false
            );

            $data = array_merge($data, [
                'selectedMonth' => $selectedMonth,
                'topMenu' => $analytics['topMenu'],
                'leastMenu' => $analytics['leastMenu'],
                'contributions' => $analytics['contributions'],
                'totalMenuSold' => $analytics['totalMenuSold'],
            ]);
        } else {
            $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false);

            $data = array_merge($data, [
                'selectedDate' => $selectedDate,
                'topMenu' => $analytics['topMenu'],
                'leastMenu' => $analytics['leastMenu'],
                'contributions' => $analytics['contributions'],
                'totalMenuSold' => $analytics['totalMenuSold'],
            ]);
        }

        return view('owner.analytics.menu', $data);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'daily');

        return match ($type) {
            'weekly' => $this->exportWeekly($request),
            'monthly' => $this->exportMonthly($request),
            default => $this->exportDaily($request),
        };
    }

    public function exportDaily(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $summary = $this->queryService->buildDailySummary($selectedDate);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false);

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
        $summary = $this->queryService->buildMonthlySummary($selectedMonth);

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

    public function exportWeekly(Request $request)
    {
        $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
        $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        $summary = $this->queryService->buildWeeklySummary($weekAnchor);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd, false);

        $filename = 'laporan-penjualan-mingguan-' . $selectedWeekStart->toDateString() . '-sampai-' . $selectedWeekEnd->toDateString() . '.csv';

        return response()->streamDownload(function () use ($selectedWeekStart, $selectedWeekEnd, $summary, $analytics) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Jenis Laporan', 'Mingguan']);
            fputcsv($output, ['Periode', $selectedWeekStart->format('Y-m-d') . ' s/d ' . $selectedWeekEnd->format('Y-m-d')]);
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
