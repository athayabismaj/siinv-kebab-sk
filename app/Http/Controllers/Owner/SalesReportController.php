<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PeriodClosing;
use App\Services\Owner\SalesReportQueryService;
use App\Services\Shared\PeriodFilterService;
use App\Services\ReportExportDispatchService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function __construct(
        private readonly SalesReportQueryService $queryService,
        private readonly PeriodFilterService $periodFilter
    ) {}

    public function index(Request $request)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        $data = ['type' => $type];

        if ($type === 'weekly') {
            $data = array_merge($data, $this->weeklySalesPayload($request));
        } elseif ($type === 'monthly') {
            $data = array_merge($data, $this->monthlySalesPayload($request));
        } else {
            $data = array_merge($data, $this->dailySalesPayload($request));
        }

        return view('owner.reports.sales_unified', $data);
    }

    public function closingIndex(Request $request)
    {
        $closings = PeriodClosing::with('closedBy')
            ->orderByDesc('period_date')
            ->paginate(12);

        $thisMonth = now()->startOfMonth();
        $isClosed = PeriodClosing::where('period_type', 'monthly')
            ->where('period_date', $thisMonth->toDateString())
            ->exists();

        $preview = $isClosed ? null : $this->queryService->buildMonthlySummary($thisMonth, true);

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

        $date = Carbon::parse((string) $request->input('period_date'))->startOfDay();

        if (PeriodClosing::where('period_type', (string) $request->input('period_type'))
            ->where('period_date', $date->toDateString())
            ->exists()) {
            return back()->with('error', 'Periode ini sudah ditutup sebelumnya.');
        }

        $summary = (string) $request->input('period_type') === 'monthly'
            ? $this->queryService->buildMonthlySummary($date, true)
            : $this->queryService->buildYearlySummary((int) $date->year, true);

        PeriodClosing::create([
            'period_type' => (string) $request->input('period_type'),
            'period_date' => $date->toDateString(),
            'total_revenue' => $summary['totalRevenue'],
            'total_transactions' => $summary['totalTransactions'],
            'closed_by_user_id' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('owner.reports.closing.index')
            ->with('success', 'Tutup buku periode ' . $date->format('M Y') . ' berhasil!');
    }

    public function menuAnalysis(Request $request)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        $data = ['type' => $type];

        if ($type === 'weekly') {
            $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
            $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
            $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd, false);

            $data = array_merge($data, [
                'selectedWeekStart' => $selectedWeekStart,
                'selectedWeekEnd' => $selectedWeekEnd,
            ], $this->analyticsPayload($analytics));
        } elseif ($type === 'monthly') {
            $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics(
                $selectedMonth->copy()->startOfMonth(),
                $selectedMonth->copy()->endOfMonth(),
                false
            );

            $data = array_merge($data, [
                'selectedMonth' => $selectedMonth,
            ], $this->analyticsPayload($analytics));
        } else {
            $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false);

            $data = array_merge($data, [
                'selectedDate' => $selectedDate,
            ], $this->analyticsPayload($analytics));
        }

        return view('owner.analytics.menu', $data);
    }

    public function export(Request $request)
    {
        try {
            $export = app(ReportExportDispatchService::class)->dispatch(
                $request->user(),
                'owner',
                'owner.sales',
                $request->query()
            );

            $message = 'Export laporan penjualan masuk antrian. ID: #' . $export->id;
            if ($export->scheduled_for) {
                $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
            }

            return redirect()
                ->route('owner.exports.index')
                ->with('success', $message);
        } catch (\Throwable) {
            return redirect()
                ->route('owner.exports.index')
                ->with('error', 'Export gagal diproses. Pastikan migrasi dan worker queue sudah aktif.');
        }
    }

    public function exportDaily(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $summary = $this->queryService->buildDailySummary($selectedDate);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false);

        $filename = 'laporan-penjualan-harian-' . $selectedDate->toDateString() . '.csv';

        return $this->streamContributionCsv(
            $filename,
            [
                ['Jenis Laporan', 'Harian'],
                ['Tanggal', $selectedDate->format('Y-m-d')],
                ['Total Omzet', (string) $summary['totalRevenue']],
                ['Jumlah Transaksi', (string) $summary['totalTransactions']],
                ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)],
            ],
            $analytics['contributions']
        );
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

        return $this->streamContributionCsv(
            $filename,
            [
                ['Jenis Laporan', 'Mingguan'],
                ['Periode', $selectedWeekStart->format('Y-m-d') . ' s/d ' . $selectedWeekEnd->format('Y-m-d')],
                ['Total Omzet', (string) $summary['totalRevenue']],
                ['Jumlah Transaksi', (string) $summary['totalTransactions']],
                ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)],
            ],
            $analytics['contributions']
        );
    }

    private function dailySalesPayload(Request $request): array
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $summary = $this->queryService->buildDailySummary($selectedDate);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate);

        return [
            'selectedDate' => $selectedDate,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'totalMenuSold' => $summary['totalMenuSold'],
            ...$this->analyticsPayload($analytics),
        ];
    }

    private function weeklySalesPayload(Request $request): array
    {
        $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
        $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        $summary = $this->queryService->buildWeeklySummary($weekAnchor);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd);

        return [
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'weeklyBreakdown' => $summary['weeklyBreakdown'],
            ...$this->analyticsPayload($analytics),
        ];
    }

    private function monthlySalesPayload(Request $request): array
    {
        $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
        $summary = $this->queryService->buildMonthlySummary($selectedMonth);
        $analytics = $this->queryService->buildPeriodMenuAnalytics(
            $selectedMonth->copy()->startOfMonth(),
            $selectedMonth->copy()->endOfMonth()
        );

        return [
            'selectedMonth' => $selectedMonth,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'dailyBreakdown' => $summary['dailyBreakdown'],
            ...$this->analyticsPayload($analytics),
        ];
    }

    private function analyticsPayload(array $analytics): array
    {
        return [
            'topMenu' => $analytics['topMenu'],
            'leastMenu' => $analytics['leastMenu'],
            'contributions' => $analytics['contributions'],
            'totalMenuSold' => $analytics['totalMenuSold'],
        ];
    }

    private function streamContributionCsv(string $filename, array $metaRows, $contributions)
    {
        return response()->streamDownload(function () use ($metaRows, $contributions) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            foreach ($metaRows as $row) {
                fputcsv($output, $row);
            }

            fputcsv($output, []);
            fputcsv($output, ['Menu', 'Qty', 'Kontribusi (%)', 'Penjualan']);

            foreach ($contributions as $item) {
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


