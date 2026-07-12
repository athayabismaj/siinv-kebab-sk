<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Models\PeriodClosing;
use App\Services\Owner\SalesReportQueryService;
use App\Services\Shared\PeriodFilterService;
use App\Support\BranchScope;
use App\Support\ReportBrand;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    use DirectExportResponse;

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
            $anchor = $data['selectedWeekStart'];
        } elseif ($type === 'monthly') {
            $data = array_merge($data, $this->monthlySalesPayload($request));
            $anchor = $data['selectedMonth'];
        } else {
            $data = array_merge($data, $this->dailySalesPayload($request));
            $anchor = $data['selectedDate'];
        }

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->periodFilter->buildNavigator($type, $anchor);

        $data = array_merge($data, compact(
            'prevFrom', 'prevTo', 'nextFrom', 'nextTo', 'isFuture', 'inputValue', 'inputType'
        ));

        $data['branchOptions'] = BranchScope::options();
        $data['branchId'] = $this->selectedBranchId($request);

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
        $branchId = $this->selectedBranchId($request);

        if ($type === 'weekly') {
            $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
            $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
            $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd, false, $branchId);
            
            $anchor = $selectedWeekStart;

            $data = array_merge($data, [
                'selectedWeekStart' => $selectedWeekStart,
                'selectedWeekEnd' => $selectedWeekEnd,
            ], $this->analyticsPayload($analytics));
        } elseif ($type === 'monthly') {
            $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics(
                $selectedMonth->copy()->startOfMonth(),
                $selectedMonth->copy()->endOfMonth(),
                false,
                $branchId
            );

            $anchor = $selectedMonth;

            $data = array_merge($data, [
                'selectedMonth' => $selectedMonth,
            ], $this->analyticsPayload($analytics));
        } else {
            $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
            $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false, $branchId);

            $anchor = $selectedDate;

            $data = array_merge($data, [
                'selectedDate' => $selectedDate,
            ], $this->analyticsPayload($analytics));
        }

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->periodFilter->buildNavigator($type, $anchor);

        $data = array_merge($data, compact(
            'prevFrom', 'prevTo', 'nextFrom', 'nextTo', 'isFuture', 'inputValue', 'inputType'
        ));

        $data['branchOptions'] = BranchScope::options();
        $data['branchId'] = $branchId;

        return view('owner.analytics.menu', $data);
    }

    public function export(Request $request)
    {
        $format = (string) $request->query('format', 'excel');
        return $this->exportDirect($request, $format);
    }

    private function exportDirect(Request $request, string $format)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        
        if ($type === 'weekly') {
            $data = $this->weeklySalesPayload($request);
            $periodeLabel = $data['selectedWeekStart']->translatedFormat('d F Y') . ' s/d ' . $data['selectedWeekEnd']->translatedFormat('d F Y');
            $fileName = 'Penjualan_' . $data['selectedWeekStart']->format('dM') . '-' . $data['selectedWeekEnd']->format('dMY');
        } elseif ($type === 'monthly') {
            $data = $this->monthlySalesPayload($request);
            $periodeLabel = $data['selectedMonth']->translatedFormat('F Y');
            $fileName = 'Penjualan_' . $data['selectedMonth']->format('M_Y');
        } else {
            $data = $this->dailySalesPayload($request);
            $periodeLabel = $data['selectedDate']->translatedFormat('d F Y');
            $fileName = 'Penjualan_' . $data['selectedDate']->format('dMY');
        }

        $periodLabels = [
            'daily' => 'HARIAN',
            'weekly' => 'MINGGUAN',
            'monthly' => 'BULANAN',
            'custom' => 'KUSTOM'
        ];
        $periodLabelText = $periodLabels[$type] ?? strtoupper($type);

        $viewData = array_merge($data, [
            'type' => $type,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'logoPath' => ReportBrand::logoPath(),
            'isExcel' => $format === 'excel',
        ]);

        return $this->exportByFormat(
            $format,
            'exports.sales_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\SalesReportExport($viewData),
                $fileName . '.xlsx'
            )
        );
    }



    private function dailySalesPayload(Request $request): array
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));
        $branchId = $this->selectedBranchId($request);
        $summary = $this->queryService->buildDailySummary($selectedDate, $branchId);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, true, $branchId);
        $transactionOverview = $this->queryService->buildPeriodTransactionOverview($selectedDate, $selectedDate, $branchId);

        return [
            'selectedDate' => $selectedDate,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'totalMenuSold' => $summary['totalMenuSold'],
            ...$this->analyticsPayload($analytics),
            ...$transactionOverview,
        ];
    }

    private function weeklySalesPayload(Request $request): array
    {
        $weekAnchor = $this->resolveSelectedDate((string) $request->input('week_date', ''));
        $branchId = $this->selectedBranchId($request);
        $selectedWeekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
        $selectedWeekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
        $summary = $this->queryService->buildWeeklySummary($weekAnchor, false, $branchId);
        $analytics = $this->queryService->buildPeriodMenuAnalytics($selectedWeekStart, $selectedWeekEnd, true, $branchId);
        $transactionOverview = $this->queryService->buildPeriodTransactionOverview($selectedWeekStart, $selectedWeekEnd, $branchId);

        return [
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'weeklyBreakdown' => $summary['weeklyBreakdown'],
            ...$this->analyticsPayload($analytics),
            ...$transactionOverview,
        ];
    }

    private function monthlySalesPayload(Request $request): array
    {
        $selectedMonth = $this->resolveSelectedMonth((string) $request->input('month', ''));
        $branchId = $this->selectedBranchId($request);
        $summary = $this->queryService->buildMonthlySummary($selectedMonth, false, $branchId);
        $analytics = $this->queryService->buildPeriodMenuAnalytics(
            $selectedMonth->copy()->startOfMonth(),
            $selectedMonth->copy()->endOfMonth(),
            true,
            $branchId
        );
        $transactionOverview = $this->queryService->buildPeriodTransactionOverview(
            $selectedMonth->copy()->startOfMonth(),
            $selectedMonth->copy()->endOfMonth(),
            $branchId
        );

        return [
            'selectedMonth' => $selectedMonth,
            'totalRevenue' => $summary['totalRevenue'],
            'totalTransactions' => $summary['totalTransactions'],
            'avgTransaction' => $summary['avgTransaction'],
            'dailyBreakdown' => $summary['dailyBreakdown'],
            ...$this->analyticsPayload($analytics),
            ...$transactionOverview,
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

    private function selectedBranchId(Request $request): ?int
    {
        return BranchScope::requestBranchId((int) $request->input('branch_id'));
    }
}
