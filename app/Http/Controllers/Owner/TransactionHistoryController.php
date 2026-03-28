<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $listQuery = Transaction::query()
            ->select([
                'id',
                'transaction_code',
                'user_id',
                'payment_method_id',
                'total_amount',
                'paid_amount',
                'change_amount',
                'created_at',
            ])
            ->with(['user:id,name,username', 'paymentMethod:id,name'])
            ->withCount('details')
            ->latest();

        $this->applyFilters($listQuery, $request, $dateFrom, $dateTo);

        $summaryQuery = Transaction::query();
        $this->applyFilters($summaryQuery, $request, $dateFrom, $dateTo);

        $totalTransactions = (clone $summaryQuery)->count();
        $totalRevenue = (float) (clone $summaryQuery)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $topCashierQuery = Transaction::query();
        $this->applyFilters($topCashierQuery, $request, $dateFrom, $dateTo);

        $topCashierRow = $topCashierQuery
            ->with('user:id,name')
            ->selectRaw('user_id, COUNT(*) as trx_count')
            ->groupBy('user_id')
            ->orderByDesc('trx_count')
            ->first();

        $topCashierName = '-';
        if ($topCashierRow && $topCashierRow->user_id) {
            $topCashierName = optional($topCashierRow->user)->name ?? '-';
        }

        $transactions = $listQuery
            ->paginate(10)
            ->withQueryString();

        $groupedTransactions = $transactions->getCollection()
            ->groupBy(fn ($trx) => $trx->created_at->toDateString());

        $paymentMethods = $this->paymentMethods();
        $cashiers = $this->cashiers();

        $type = $request->input('type', 'daily');
        $todayDate = now()->startOfDay();

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
            $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subDay()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addDay()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addDay()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m-d'); 
            $inputType = 'date';
        }

        return view('owner.transactions.index', [
            'transactions'       => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'paymentMethods'     => $paymentMethods,
            'cashiers'           => $cashiers,
            'dateFrom'           => $dateFrom,
            'dateTo'             => $dateTo,
            'totalTransactions'  => $totalTransactions,
            'totalRevenue'       => $totalRevenue,
            'avgTransaction'     => $avgTransaction,
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
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = Transaction::query()
            ->select([
                'id',
                'transaction_code',
                'user_id',
                'payment_method_id',
                'total_amount',
                'paid_amount',
                'change_amount',
                'created_at',
            ])
            ->with(['user:id,name,username', 'paymentMethod:id,name'])
            ->withCount('details')
            ->latest();

        $this->applyFilters($query, $request, $dateFrom, $dateTo);

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

    private function applyFilters(Builder $query, Request $request, Carbon $dateFrom, Carbon $dateTo): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', (int) $request->input('payment_method_id'));
        }

        $query->whereDate('created_at', '>=', $dateFrom->toDateString())
              ->whereDate('created_at', '<=', $dateTo->toDateString());
    }

    private function resolveDateRange(Request $request): array
    {
        $today = now()->startOfDay();

        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : $today;

        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->startOfDay()
            : $today;

        // Prevent future dates
        if ($from->greaterThan($today)) $from = $today;
        if ($to->greaterThan($today))   $to   = $today;

        // Ensure from <= to
        if ($from->greaterThan($to)) [$from, $to] = [$to, $from];

        return [$from, $to];
    }
}
