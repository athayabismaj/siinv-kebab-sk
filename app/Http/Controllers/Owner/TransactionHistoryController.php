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
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));

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

        $this->applyFilters($listQuery, $request, $selectedDate);

        $summaryQuery = Transaction::query();
        $this->applyFilters($summaryQuery, $request, $selectedDate);

        $totalTransactions = (clone $summaryQuery)->count();
        $totalRevenue = (float) (clone $summaryQuery)->sum('total_amount');
        $avgTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        $topCashierQuery = Transaction::query();
        $this->applyFilters($topCashierQuery, $request, $selectedDate);

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

        return view('owner.transactions.index', [
            'transactions' => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'paymentMethods' => $paymentMethods,
            'cashiers' => $cashiers,
            'selectedDate' => $selectedDate,
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $avgTransaction,
            'topCashierName' => $topCashierName,
        ]);
    }

    public function export(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate((string) $request->input('date', ''));

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

        $this->applyFilters($query, $request, $selectedDate);

        $rows = $query->get();

        $filename = 'riwayat-transaksi-owner-' . $selectedDate->toDateString() . '.csv';

        return response()->streamDownload(function () use ($rows, $selectedDate) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Tanggal Laporan', $selectedDate->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, [
                'Kode',
                'Kasir',
                'Metode Pembayaran',
                'Status',
                'Jumlah Item',
                'Total',
                'Dibayar',
                'Kembalian',
                'Waktu',
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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    private function applyFilters(Builder $query, Request $request, Carbon $selectedDate): void
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

        $query->whereDate('created_at', '=', $selectedDate->toDateString());
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
}
