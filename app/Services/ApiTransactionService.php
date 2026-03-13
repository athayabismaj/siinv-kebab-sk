<?php

namespace App\Services;

use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ApiTransactionService
{
    public function getHistory(int $userId, ?string $date, int $perPage = 15): LengthAwarePaginator
    {
        $itemCountSubQuery = DB::table('transaction_details')
            ->selectRaw('transaction_id, COUNT(*) as items_count')
            ->groupBy('transaction_id');

        $query = $this->baseUserTransactionQuery($userId, $date)
            ->leftJoinSub($itemCountSubQuery, 'td', function ($join) {
                $join->on('td.transaction_id', '=', 't.id');
            })
            ->select([
                't.id',
                't.transaction_code',
                't.total_amount',
                't.created_at',
                DB::raw('COALESCE(td.items_count, 0) as items_count'),
            ])
            ->orderByDesc('t.created_at');

        return $query->paginate($perPage);
    }

    public function getRevenueSummary(int $userId, ?string $date): array
    {
        $query = $this->baseUserTransactionQuery($userId, $date);

        return [
            'total_revenue' => (float) (clone $query)->sum('t.total_amount'),
            'total_count' => (int) (clone $query)->count(),
        ];
    }

    public function getRevenueTrend(int $userId, ?string $dateInput): array
    {
        $endDate = $this->resolveDateOrNow($dateInput);
        $startDate = $endDate->copy()->subDays(6);

        $trendData = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total_revenue'))
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $currentDate,
                'total_revenue' => (float) ($trendData->has($currentDate) ? $trendData[$currentDate]->total_revenue : 0),
            ];
        }

        return $result;
    }

    public function normalizePaymentMethod(Request $request): void
    {
        $paymentMethodId = $request->input('payment_method_id');
        if (is_string($paymentMethodId) && is_numeric($paymentMethodId)) {
            $request->merge(['payment_method_id' => (int) $paymentMethodId]);
            return;
        }

        if (! empty($paymentMethodId)) {
            return;
        }

        $paymentMethodValue = $request->input('payment_method');
        if (is_array($paymentMethodValue)) {
            $paymentMethodObjectId = Arr::get($paymentMethodValue, 'id');
            if (is_numeric($paymentMethodObjectId)) {
                $request->merge(['payment_method_id' => (int) $paymentMethodObjectId]);
                return;
            }
        }

        $paymentMethodName = trim((string) (
            $request->input('payment_method_name')
            ?? $request->input('payment_method')
            ?? $request->input('payment_method_label')
            ?? Arr::get($request->input('payment_method'), 'name')
        ));

        if ($paymentMethodName === '') {
            return;
        }

        $methodId = PaymentMethod::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(name) = ?', [strtolower($paymentMethodName)])
            ->value('id');

        if ($methodId) {
            $request->merge(['payment_method_id' => (int) $methodId]);
        }
    }

    public function buildCheckoutDraft(array $validated): array
    {
        $lineItems = [];
        $totalAmount = 0.0;

        $paymentMethod = PaymentMethod::query()
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->findOrFail((int) $validated['payment_method_id']);

        foreach ($validated['items'] as $item) {
            $variant = MenuVariant::query()
                ->with('menu')
                ->findOrFail((int) $item['variant_id']);

            if (! $variant->is_available || ! optional($variant->menu)->is_active) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => "Variant '{$variant->name}' tidak tersedia untuk dijual.",
                ];
            }

            $qty = (int) $item['qty'];
            $price = array_key_exists('price', $item) && $item['price'] !== null
                ? (float) $item['price']
                : (float) $variant->price;

            $subtotal = $price * $qty;
            $totalAmount += $subtotal;

            $lineItems[] = [
                'variant_id' => (int) $item['variant_id'],
                'variant_name' => $variant->name,
                'menu_id' => (int) $variant->menu_id,
                'menu_name' => optional($variant->menu)->name,
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $paidAmount = (float) $validated['paid_amount'];
        if ($paidAmount < $totalAmount) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Nominal pembayaran kurang dari total transaksi.',
                'data' => [
                    'total_amount' => round($totalAmount, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'deficit_amount' => round($totalAmount - $paidAmount, 2),
                ],
            ];
        }

        return [
            'ok' => true,
            'payment_method' => $paymentMethod,
            'line_items' => $lineItems,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ];
    }

    public function createCheckoutTransaction(int $userId, array $draft, ?string $note): array
    {
        return DB::transaction(function () use ($userId, $draft, $note) {
            DB::statement("SET LOCAL lock_timeout = '5s'");
            DB::statement("SET LOCAL statement_timeout = '12s'");

            $now = now();
            $transactionCode = $this->generateTransactionCode();

            $transactionId = DB::table('transactions')->insertGetId([
                'transaction_code' => $transactionCode,
                'user_id' => $userId,
                'total_amount' => $draft['total_amount'],
                'payment_method_id' => (int) $draft['payment_method']->id,
                'paid_amount' => $draft['paid_amount'],
                'change_amount' => $draft['paid_amount'] - $draft['total_amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($draft['line_items'] as $line) {
                DB::table('transaction_details')->insert([
                    'transaction_id' => $transactionId,
                    'menu_id' => $line['menu_id'],
                    'quantity' => $line['qty'],
                    'price' => $line['price'],
                    'subtotal' => $line['subtotal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                StockService::deductStock(
                    $line['variant_id'],
                    $line['qty'],
                    $transactionId,
                    $note
                );
            }

            return [
                'transaction_id' => $transactionId,
                'transaction_code' => $transactionCode,
                'created_at' => $now->toIso8601String(),
                'payment_method' => [
                    'id' => $draft['payment_method']->id,
                    'name' => $draft['payment_method']->name,
                ],
                'items' => collect($draft['line_items'])->map(fn ($line) => [
                    'menu_id' => $line['menu_id'],
                    'menu_name' => $line['menu_name'],
                    'variant_id' => $line['variant_id'],
                    'variant_name' => $line['variant_name'],
                    'qty' => $line['qty'],
                    'price' => round($line['price'], 2),
                    'subtotal' => round($line['subtotal'], 2),
                ])->values(),
                'total_amount' => round($draft['total_amount'], 2),
                'paid_amount' => round($draft['paid_amount'], 2),
                'change_amount' => round($draft['paid_amount'] - $draft['total_amount'], 2),
            ];
        });
    }

    private function baseUserTransactionQuery(int $userId, ?string $date): Builder
    {
        $query = DB::table('transactions as t')->where('t.user_id', $userId);

        if (! empty($date)) {
            $query->whereDate('t.created_at', $date);
        }

        return $query;
    }

    private function resolveDateOrNow(?string $dateInput): Carbon
    {
        try {
            return $dateInput
                ? Carbon::parse($dateInput)
                : Carbon::now();
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    private function generateTransactionCode(): string
    {
        $datePrefix = now()->format('Ymd');

        $latestTransaction = DB::table('transactions')
            ->where('transaction_code', 'like', "TRX-{$datePrefix}-%")
            ->orderByDesc('id')
            ->first();

        if ($latestTransaction) {
            $sequence = (int) substr($latestTransaction->transaction_code, -4);
            $nextSequence = str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return "TRX-{$datePrefix}-{$nextSequence}";
    }
}
