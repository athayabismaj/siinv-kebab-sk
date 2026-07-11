<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\Owner\TransactionHistoryQueryService;
use App\Services\Shared\PeriodFilterService;
use App\Support\AdminCache;
use App\Support\ReportBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryController extends Controller
{
    use DirectExportResponse;

    public function __construct(
        private readonly TransactionHistoryQueryService $queryService,
        private readonly PeriodFilterService $periodFilter
    ) {}

    public function index(Request $request)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);
        $filters = $request->only(['search', 'user_id', 'payment_method_id']);

        $listQuery = $this->queryService
            ->applyFilters(
                $this->queryService->baseListQuery($dateFrom, $dateTo),
                $filters
            )
            ->latest();

        $summary = $this->queryService->summary($dateFrom, $dateTo, $filters);
        $topCashierName = $this->queryService->topCashierName($dateFrom, $dateTo, $filters);

        $transactions = $listQuery
            ->paginate(10)
            ->withQueryString();

        $groupedTransactions = $transactions->getCollection()
            ->groupBy(fn ($trx) => $trx->created_at->toDateString());

        $paymentMethods = $this->paymentMethods();
        $cashiers = $this->cashiers();

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->periodFilter->buildNavigator($type, $dateFrom);

        return view('owner.transactions.index', [
            'transactions'       => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'paymentMethods'     => $paymentMethods,
            'cashiers'           => $cashiers,
            'dateFrom'           => $dateFrom,
            'dateTo'             => $dateTo,
            'totalTransactions'  => $summary['total_transactions'],
            'totalRevenue'       => $summary['total_revenue'],
            'avgTransaction'     => $summary['avg_transaction'],
            'topCashierName'     => $topCashierName,
            'type'               => $type,
            'prevFrom'           => $prevFrom,
            'prevTo'             => $prevTo,
            'nextFrom'           => $nextFrom,
            'nextTo'             => $nextTo,
            'isFuture'           => $isFuture,
            'inputValue'         => $inputValue,
            'inputType'          => $inputType,
        ]);
    }

    public function export(Request $request)
    {
        $format = (string) $request->query('format', 'excel');
        return $this->exportDirect($request, $format);
    }

    private function exportDirect(Request $request, string $format)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);
        $filters = $request->only(['search', 'user_id', 'payment_method_id']);

        $listQuery = $this->queryService
            ->applyFilters(
                $this->queryService->baseListQuery($dateFrom, $dateTo),
                $filters
            )
            ->latest();

        $summary = $this->queryService->summary($dateFrom, $dateTo, $filters);
        
        // Disable pagination for export, get all data
        $transactions = $listQuery->get();

        $periodeLabel = $dateFrom->translatedFormat('d F Y');
        if (!$dateFrom->isSameDay($dateTo)) {
            $periodeLabel .= ' - ' . $dateTo->translatedFormat('d F Y');
        }

        $periodLabels = [
            'daily' => 'HARIAN',
            'weekly' => 'MINGGUAN',
            'monthly' => 'BULANAN',
            'custom' => 'KUSTOM'
        ];
        $periodLabelText = $periodLabels[$type] ?? strtoupper($type);

        $viewData = [
            'transactions' => $transactions,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'logoPath' => ReportBrand::logoPath(),
            'isExcel' => $format === 'excel',
        ];

        $fileName = 'riwayat-transaksi-' . $dateFrom->format('Y-m-d');
        if (!$dateFrom->isSameDay($dateTo)) {
            $fileName .= '-sd-' . $dateTo->format('Y-m-d');
        }

        return $this->exportByFormat(
            $format,
            'exports.transaction_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\TransactionReportExport($viewData),
                $fileName . '.xlsx'
            ),
            'A4',
            'landscape'
        );
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'user:id,name,username',
            'voidedBy:id,name,username',
            'paymentMethod:id,name',
            'details.menu:id,name',
        ]);

        return view('owner.transactions.show', compact('transaction'));
    }

    private function paymentMethods()
    {
        return Cache::remember(
            AdminCache::key('payment_methods', 'owner:list'),
            now()->addSeconds(90),
            fn () => PaymentMethod::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function cashiers()
    {
        return Cache::remember(
            AdminCache::key('transactions', 'owner:cashiers:list'),
            now()->addSeconds(90),
            fn () => Transaction::query()
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get()
        );
    }

}
