<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $isOwnerView = $this->isOwnerView($request);
        $selectedDate = $isOwnerView
            ? null
            : $this->resolveSelectedDate((string) $request->input('date', ''));

        $query = $this->baseTransactionQuery();

        $this->applyCommonFilters($query, $request);
        $this->applyDateFilters($query, $request, $isOwnerView, $selectedDate);

        $transactions = $query
            ->paginate(10)
            ->withQueryString();

        $viewData = $this->buildViewData($request, $transactions, $selectedDate, $isOwnerView);
        $viewData['paymentMethods'] = $this->paymentMethodOptions();
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
                'created_at',
            ])
            ->with(['user:id,name,username', 'paymentMethod:id,name'])
            ->withCount('details')
            ->latest();
    }

    private function applyCommonFilters($query, Request $request): void
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

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', (int) $request->input('payment_method_id'));
        }
    }

    private function applyDateFilters($query, Request $request, bool $isOwnerView, ?Carbon $selectedDate): void
    {
        if ($isOwnerView) {
            $this->applyOwnerDateRange($query, $request);
            return;
        }

        if ($selectedDate !== null) {
            $query->whereBetween('created_at', [
                $selectedDate->copy()->startOfDay(),
                $selectedDate->copy()->endOfDay(),
            ]);
        }
    }

    private function applyOwnerDateRange($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            try {
                $from = Carbon::parse((string) $request->input('date_from'))->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Throwable) {
                // abaikan filter tanggal tidak valid
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = Carbon::parse((string) $request->input('date_to'))->endOfDay();
                $query->where('created_at', '<=', $to);
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

        $dateNavParams = $this->buildDateNavigationParams($request, $activeDate);
        $data['prevDateParams'] = $dateNavParams['prev'];
        $data['nextDateParams'] = $dateNavParams['next'];

        $data['hasActiveFilters'] = $request->filled('search')
            || $request->filled('payment_method_id')
            || $request->filled('date');

        $data['groupedTransactions'] = $transactions->getCollection()
            ->groupBy(fn ($trx) => $trx->created_at->toDateString())
            ->map(function ($items, $date) {
                $groupDate = Carbon::parse($date);
                $label = $groupDate->translatedFormat('d M Y');

                if ($groupDate->isToday()) {
                    $label = 'Hari ini';
                } elseif ($groupDate->isYesterday()) {
                    $label = 'Kemarin';
                }

                return [
                    'date' => $date,
                    'label' => $label,
                    'items' => $items,
                ];
            });

        return $data;
    }

    private function paymentMethodOptions()
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

    private function resolveSelectedDate(string $dateInput): ?Carbon
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

    private function buildDateNavigationParams(Request $request, Carbon $activeDate): array
    {
        $base = array_filter([
            'search' => $request->input('search'),
            'payment_method_id' => $request->input('payment_method_id'),
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'prev' => array_merge($base, ['date' => $activeDate->copy()->subDay()->toDateString()]),
            'next' => array_merge($base, ['date' => $activeDate->copy()->addDay()->toDateString()]),
        ];
    }
}
