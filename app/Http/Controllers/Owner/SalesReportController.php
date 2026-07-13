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
use Illuminate\Support\Facades\Schema;

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
        $usesBranchClosing = $this->periodClosingUsesBranches();
        $branchOptions = BranchScope::options();
        $branchId = $usesBranchClosing ? $this->closingBranchId($request, $branchOptions) : null;
        $selectedBranch = $branchId ? $branchOptions->firstWhere('id', $branchId) : null;

        $closings = PeriodClosing::with($usesBranchClosing ? ['closedBy', 'branch'] : ['closedBy'])
            ->when($usesBranchClosing && $branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderByDesc('period_date')
            ->orderByDesc('created_at')
            ->paginate(12);
        $cancelableClosingIds = $closings->getCollection()
            ->mapWithKeys(fn (PeriodClosing $closing) => [
                $closing->id => $this->isLatestClosing($closing, $usesBranchClosing),
            ]);

        $thisMonth = now()->startOfMonth();
        $isClosed = false;
        $preview = null;

        if (! $usesBranchClosing || $branchId) {
            $isClosedQuery = PeriodClosing::where('period_type', 'monthly')
                ->where('period_date', $thisMonth->toDateString());

            if ($usesBranchClosing) {
                $isClosedQuery->where('branch_id', $branchId);
            }

            $isClosed = $isClosedQuery->exists();
            $preview = $isClosed ? null : $this->queryService->buildMonthlySummary($thisMonth, true, $usesBranchClosing ? $branchId : null);
        }

        return view('owner.reports.closing_index', [
            'closings' => $closings,
            'preview' => $preview,
            'isClosed' => $isClosed,
            'thisMonth' => $thisMonth,
            'branchOptions' => $branchOptions,
            'branchId' => $branchId,
            'selectedBranch' => $selectedBranch,
            'usesBranchClosing' => $usesBranchClosing,
            'cancelableClosingIds' => $cancelableClosingIds,
        ]);
    }

    public function closePeriod(Request $request)
    {
        $usesBranchClosing = $this->periodClosingUsesBranches();
        $rules = [
            'period_type' => 'required|in:monthly,yearly',
            'period_date' => 'required|date',
        ];

        if ($usesBranchClosing) {
            $rules['branch_id'] = 'required|integer|exists:branches,id';
        }

        $request->validate($rules);

        $date = Carbon::parse((string) $request->input('period_date'))->startOfDay();
        $branchId = $usesBranchClosing ? BranchScope::requestBranchId((int) $request->input('branch_id')) : null;

        if ($usesBranchClosing && ! $branchId) {
            return back()->with('error', 'Cabang tutup buku tidak valid atau sudah tidak aktif.');
        }

        $existingClosing = PeriodClosing::where('period_type', (string) $request->input('period_type'))
            ->where('period_date', $date->toDateString());

        if ($usesBranchClosing) {
            $existingClosing->where('branch_id', $branchId);
        }

        if ($existingClosing->exists()) {
            return back()->with('error', $usesBranchClosing
                ? 'Periode cabang ini sudah ditutup sebelumnya.'
                : 'Periode ini sudah ditutup sebelumnya.');
        }

        $summary = (string) $request->input('period_type') === 'monthly'
            ? $this->queryService->buildMonthlySummary($date, true, $branchId)
            : $this->queryService->buildYearlySummary((int) $date->year, true, $branchId);

        $payload = [
            'period_type' => (string) $request->input('period_type'),
            'period_date' => $date->toDateString(),
            'total_revenue' => $summary['totalRevenue'],
            'total_transactions' => $summary['totalTransactions'],
            'closed_by_user_id' => auth()->id(),
            'notes' => $request->input('notes'),
        ];

        if ($usesBranchClosing) {
            $payload['branch_id'] = $branchId;
        }

        PeriodClosing::create($payload);

        return redirect()
            ->route('owner.reports.closing.index')
            ->with('success', 'Tutup buku periode ' . $date->format('M Y') . ' berhasil disimpan untuk cabang terpilih.');
    }

    public function cancelClosing(Request $request, PeriodClosing $closing)
    {
        $request->validate([
            'confirmation' => 'required|string',
        ], [
            'confirmation.required' => 'Konfirmasi pembatalan wajib diisi.',
        ]);

        if (strtoupper(trim((string) $request->input('confirmation'))) !== 'BATALKAN') {
            return back()->with('error', 'Pembatalan ditolak. Ketik BATALKAN untuk membatalkan tutup buku.');
        }

        $usesBranchClosing = $this->periodClosingUsesBranches();
        $latestClosingQuery = PeriodClosing::query()
            ->where('period_type', $closing->period_type);

        if ($usesBranchClosing) {
            $latestClosingQuery->where('branch_id', $closing->branch_id);
        }

        $latestClosing = $latestClosingQuery
            ->orderByDesc('period_date')
            ->orderByDesc('created_at')
            ->first();

        if (! $latestClosing || (int) $latestClosing->id !== (int) $closing->id) {
            return back()->with('error', 'Hanya tutup buku periode terakhir yang dapat dibatalkan.');
        }

        $periodLabel = $closing->period_type === 'monthly'
            ? $closing->period_date->translatedFormat('F Y')
            : $closing->period_date->format('Y');
        $branchLabel = $usesBranchClosing ? ($closing->branch->name ?? 'Cabang lama') : 'Sistem';

        logger()->warning('period_closing_cancelled', [
            'period_closing_id' => $closing->id,
            'period_type' => $closing->period_type,
            'period_date' => $closing->period_date?->toDateString(),
            'branch_id' => $closing->branch_id,
            'cancelled_by_user_id' => auth()->id(),
        ]);

        $closing->delete();

        return redirect()
            ->route('owner.reports.closing.index')
            ->with('success', "Tutup buku {$periodLabel} untuk {$branchLabel} berhasil dibatalkan.");
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
        return BranchScope::ownerBranchId((int) $request->input('branch_id'));
    }

    private function closingBranchId(Request $request, $branchOptions): ?int
    {
        $branchId = $this->selectedBranchId($request);

        if ($branchId) {
            return $branchId;
        }

        return $branchOptions->count() === 1 ? (int) $branchOptions->first()->id : null;
    }

    private function periodClosingUsesBranches(): bool
    {
        return BranchScope::hasBranchesTable()
            && Schema::hasColumn('period_closings', 'branch_id');
    }

    private function isLatestClosing(PeriodClosing $closing, bool $usesBranchClosing): bool
    {
        $query = PeriodClosing::query()
            ->where('period_type', $closing->period_type);

        if ($usesBranchClosing) {
            $query->where('branch_id', $closing->branch_id);
        }

        $latestClosing = $query
            ->orderByDesc('period_date')
            ->orderByDesc('created_at')
            ->first(['id']);

        return $latestClosing && (int) $latestClosing->id === (int) $closing->id;
    }
}
