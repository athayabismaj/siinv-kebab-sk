<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\Owner\TransactionHistoryQueryService;
use App\Services\ReportExportDispatchService;
use App\Services\Shared\PeriodFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryController extends Controller
{
    public function __construct(
        private readonly TransactionHistoryQueryService $queryService,
        private readonly PeriodFilterService $periodFilter,
        private readonly ReportExportDispatchService $exportDispatch
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
        $export = $this->exportDispatch->dispatch(
            $request->user(),
            'owner',
            'owner.transactions',
            $request->query()
        );

        $message = 'Export transaksi masuk antrian. ID: #' . $export->id;
        if ($export->scheduled_for) {
            $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
        }

        return redirect()
            ->route('owner.exports.index')
            ->with('success', $message);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'user:id,name,username',
            'paymentMethod:id,name',
            'details.menu:id,name',
        ]);

        return view('owner.transactions.show', compact('transaction'));
    }

    private function paymentMethods()
    {
        return Cache::remember(
            'payment_methods:list',
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
            'owner:transaction_cashiers:list',
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

