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
        $isOwnerView = $request->routeIs('owner.*');
        $selectedDate = $isOwnerView
            ? null
            : $this->resolveSelectedDate((string) $request->input('date', ''));

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

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', (int) $request->payment_method_id);
        }

        if ($isOwnerView) {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        } elseif ($selectedDate !== null) {
            $query->whereDate('created_at', '=', $selectedDate->toDateString());
        }

        $transactions = $query
            ->paginate(10)
            ->withQueryString();

        $paymentMethods = Cache::remember(
            'payment_methods:list',
            now()->addMinutes(2),
            fn () => PaymentMethod::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        $view = $isOwnerView
            ? 'owner.transactions.index'
            : 'admin.transactions.index';

        return view($view, compact('transactions', 'paymentMethods', 'selectedDate'));
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
}
