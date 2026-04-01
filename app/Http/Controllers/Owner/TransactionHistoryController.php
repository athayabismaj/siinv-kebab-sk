<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\Owner\TransactionHistoryQueryService;
use App\Services\Shared\PeriodFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryController extends Controller
{
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
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);
        $filters = $request->only(['search', 'user_id', 'payment_method_id']);
        $query = $this->queryService
            ->applyFilters(
                $this->queryService->baseListQuery($dateFrom, $dateTo),
                $filters
            )
            ->latest();

        $rows = $query->get();

        $filename = 'riwayat-transaksi-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, [
                'Kode', 'Kasir', 'Metode Pembayaran', 'Status',
                'Jumlah Item', 'Total', 'Dibayar', 'Kembalian', 'Waktu',
            ]);

            foreach ($rows as $trx) {
                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                fputcsv($output, [
                    $trx->transaction_code,
                    optional($trx->user)->name ?? '-',
                    optional($trx->paymentMethod)->name ?? '-',
                    $isPaid ? 'Lunas' : 'Kurang',
                    (int) $trx->details_count,
                    (float) $trx->total_amount,
                    (float) $trx->paid_amount,
                    (float) $trx->change_amount,
                    $trx->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
            now()->addMinutes(2),
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
            now()->addMinutes(2),
            fn () => Transaction::query()
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get()
        );
    }

}
