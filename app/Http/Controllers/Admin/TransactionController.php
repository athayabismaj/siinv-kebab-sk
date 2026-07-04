<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Support\AdminCache;
use App\Support\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $isOwnerView = $this->isOwnerView($request);
        $selectedDate = null;
        $type = 'daily';
        $dateFrom = null;
        $dateTo = null;

        if (! $isOwnerView) {
            $request->query->remove('payment_method_id');
        }

        if (!$isOwnerView) {
            $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
            [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);
            $selectedDate = $dateFrom->copy();
        }

        $query = $this->baseTransactionQuery();

        $this->applyCommonFilters(
            $query,
            $request,
            includeUserFilter: ! $isOwnerView,
            includePaymentMethodFilter: $isOwnerView
        );
        $this->applyDateFilters($query, $request, $isOwnerView, $selectedDate, $dateTo);

        $transactions = $query
            ->paginate(10)
            ->withQueryString();

        $viewData = $this->buildViewData($request, $transactions, $selectedDate, $isOwnerView);
        $viewData['type'] = $type;
        $viewData['dateFrom'] = $dateFrom;
        $viewData['dateTo'] = $dateTo;
        $viewData['paymentMethods'] = $this->paymentMethodOptions();
        $viewData['cashiers'] = $this->cashierOptions();
        $viewData['transactions'] = $transactions;

        $view = $isOwnerView
            ? 'owner.transactions.index'
            : 'admin.transactions.index';

        return view($view, $viewData);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $transaction->load([
            'user:id,name,username',
            'paymentMethod:id,name',
            'details.menu:id,name',
        ]);

        $view = $request->routeIs('owner.*')
            ? 'owner.transactions.show'
            : 'admin.transactions.show';

        return view($view, compact('transaction'));
    }

    private function isOwnerView(Request $request): bool
    {
        return $request->routeIs('owner.*');
    }

    private function baseTransactionQuery()
    {
        return Transaction::query()
            ->select([
                'id',
                'transaction_code',
                'user_id',
                'payment_method_id',
                'total_amount',
                'paid_amount',
                'change_amount',
                'status',
                'created_at',
            ])
            ->with(['user:id,name,username', 'paymentMethod:id,name'])
            ->withCount('details')
            ->latest();
    }

    private function applyCommonFilters(
        $query,
        Request $request,
        bool $includeUserFilter = false,
        bool $includePaymentMethodFilter = true
    ): void
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

        if ($includePaymentMethodFilter && $request->filled('payment_method_id')) {
            $query->where('payment_method_id', (int) $request->input('payment_method_id'));
        }

        if ($includeUserFilter && $request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }
    }

    private function applyDateFilters($query, Request $request, bool $isOwnerView, ?Carbon $selectedDate, ?Carbon $selectedEndDate = null): void
    {
        if ($isOwnerView) {
            $this->applyOwnerDateRange($query, $request);
            return;
        }

        if ($selectedDate !== null) {
            $query->whereBetween('transactions.created_at', [
                $selectedDate->copy()->startOfDay(),
                ($selectedEndDate ?? $selectedDate)->copy()->endOfDay(),
            ]);
        }
    }

    private function applyOwnerDateRange($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            try {
                $from = Carbon::parse((string) $request->input('date_from'))->startOfDay();
                $query->where('transactions.created_at', '>=', $from);
            } catch (\Throwable) {
                // abaikan filter tanggal tidak valid
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = Carbon::parse((string) $request->input('date_to'))->endOfDay();
                $query->where('transactions.created_at', '<=', $to);
            } catch (\Throwable) {
                // abaikan filter tanggal tidak valid
            }
        }
    }

    private function buildViewData(Request $request, $transactions, ?Carbon $selectedDate, bool $isOwnerView): array
    {
        $data = [
            'selectedDate' => $selectedDate,
            'activeDate' => null,
            'todayDate' => null,
            'isToday' => false,
            'prevDateParams' => [],
            'nextDateParams' => [],
            'hasActiveFilters' => false,
            'groupedTransactions' => collect(),
        ];

        if ($isOwnerView) {
            return $data;
        }

        $activeDate = $selectedDate ?? now()->startOfDay();
        $todayDate = now()->startOfDay();

        $data['activeDate'] = $activeDate;
        $data['todayDate'] = $todayDate;
        $data['isToday'] = $activeDate->isSameDay($todayDate);

        $data['hasActiveFilters'] = $request->filled('search')
            || $request->filled('user_id')
            || $request->filled('date_from')
            || $request->filled('date_to');

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            ReportPeriod::buildNavigator((string) $request->input('type', 'daily'), $activeDate);

        $data['prevFrom'] = $prevFrom;
        $data['prevTo'] = $prevTo;
        $data['nextFrom'] = $nextFrom;
        $data['nextTo'] = $nextTo;
        $data['isFuture'] = $isFuture;
        $data['inputValue'] = $inputValue;
        $data['inputType'] = $inputType;

        $data['groupedTransactions'] = $transactions->getCollection()
            ->groupBy(fn ($trx) => $trx->created_at->toDateString())
            ->map(fn ($items) => $items);

        $summary = $this->buildSummary($request, $selectedDate);
        $data['totalTransactions'] = $summary['total_transactions'];
        $data['totalRevenue'] = $summary['total_revenue'];
        $data['avgTransaction'] = $summary['avg_transaction'];
        $data['topCashierName'] = $summary['top_cashier_name'];

        return $data;
    }

    private function paymentMethodOptions()
    {
        return Cache::remember(
            AdminCache::key('payment_methods', 'list'),
            now()->addMinutes(2),
            fn () => PaymentMethod::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function cashierOptions()
    {
        return Cache::remember(
            AdminCache::key('transactions', 'cashiers:list'),
            now()->addSeconds(90),
            fn () => Transaction::query()
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get()
        );
    }

    private function buildSummary(Request $request, ?Carbon $selectedDate): array
    {
        $summaryEndDate = null;
        if ($request->filled('date_to')) {
            try {
                $summaryEndDate = Carbon::parse((string) $request->input('date_to'))->startOfDay();
            } catch (\Throwable) {
                $summaryEndDate = null;
            }
        }

        $suffix = 'summary:' . md5(json_encode([
            'search' => (string) $request->input('search', ''),
            'user_id' => (int) $request->input('user_id', 0),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'selected_date' => $selectedDate?->toDateString(),
            'summary_end' => $summaryEndDate?->toDateString(),
            'type' => (string) $request->input('type', 'daily'),
        ]));

        return Cache::remember(
            AdminCache::key('transactions', $suffix),
            now()->addSeconds(90),
            function () use ($request, $selectedDate, $summaryEndDate) {
                $summaryQuery = Transaction::query();
                $this->applyCommonFilters(
                    $summaryQuery,
                    $request,
                    includeUserFilter: true,
                    includePaymentMethodFilter: false
                );
                $this->applyDateFilters($summaryQuery, $request, false, $selectedDate, $summaryEndDate);

                $aggregate = (clone $summaryQuery)
                    ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
                    ->first();

                $totalTransactions = (int) ($aggregate->total_transactions ?? 0);
                $totalRevenue = (float) ($aggregate->total_revenue ?? 0);

                $topCashier = (clone $summaryQuery)
                    ->join('users', 'users.id', '=', 'transactions.user_id')
                    ->select('users.name', DB::raw('COUNT(*) as trx_count'))
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('trx_count')
                    ->first();

                return [
                    'total_transactions' => $totalTransactions,
                    'total_revenue' => $totalRevenue,
                    'avg_transaction' => $totalTransactions > 0
                        ? ($totalRevenue / $totalTransactions)
                        : 0,
                    'top_cashier_name' => (string) ($topCashier?->name ?? '-'),
                ];
            }
        );
    }
}
