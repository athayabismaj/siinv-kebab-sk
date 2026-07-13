<?php

namespace App\Services;

use App\Models\DailyTarget;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApiTransactionService
{
    public function __construct(
        private readonly VariantAvailabilityService $variantAvailabilityService
    ) {
    }

    public function getHistory(int $userId, ?string $date, int $perPage = 15): LengthAwarePaginator
    {
        // Avoid global aggregate subquery on transaction_details that can become expensive on large datasets.
        // Count detail rows only for the filtered transaction rows.
        $query = $this->baseUserTransactionQuery($userId, $date)
            ->leftJoin('transaction_details as td', 'td.transaction_id', '=', 't.id')
            ->select([
                't.id',
                't.transaction_code',
                't.total_amount',
                't.status',
                't.created_at',
                DB::raw('COUNT(td.id) as items_count'),
            ])
            ->groupBy('t.id', 't.transaction_code', 't.total_amount', 't.status', 't.created_at')
            ->orderByDesc('t.created_at');

        return $query->paginate($perPage);
    }

    public function getTransactionDetail(string $transactionKey, int $userId, bool $canReadAll = false): ?array
    {
        $transaction = Transaction::query()
            ->with([
                'paymentMethod:id,name',
                'details.menu:id,name',
                'details.menuVariant:id,name',
            ])
            ->when(
                ctype_digit($transactionKey),
                fn ($query) => $query->where('id', (int) $transactionKey),
                fn ($query) => $query->where('transaction_code', $transactionKey)
            )
            ->when(! $canReadAll, fn ($query) => $query->where('user_id', $userId))
            ->first();

        if (! $transaction) {
            return null;
        }

        $timezone = config('app.timezone', 'Asia/Jakarta');

        return [
            'id' => (int) $transaction->id,
            'transaction_code' => (string) $transaction->transaction_code,
            'created_at' => Carbon::parse($transaction->created_at)->setTimezone($timezone)->format('Y-m-d H:i:s'),
            'status' => strtoupper((string) ($transaction->status ?? 'SUCCESS')),
            'payment_method_name' => $transaction->paymentMethod?->name,
            'total_amount' => round((float) $transaction->total_amount, 2),
            'paid_amount' => round((float) $transaction->paid_amount, 2),
            'change_amount' => round((float) $transaction->change_amount, 2),
            'items' => $transaction->details
                ->map(fn ($detail) => [
                    'menu_name' => $detail->menu?->name,
                    'variant_name' => $detail->menuVariant?->name,
                    'qty' => (int) $detail->quantity,
                    'price' => round((float) $detail->price, 2),
                    'subtotal' => round((float) $detail->subtotal, 2),
                ])
                ->values(),
        ];
    }

    public function getRevenueSummary(int $userId, ?string $date): array
    {
        $selectedDate = $this->resolveDateOrNow($date)->toDateString();
        $query = $this->baseUserTransactionQuery($userId, $selectedDate)
            ->where(function (Builder $query) {
                $query->whereNull('t.status')
                    ->orWhereRaw('LOWER(t.status) <> ?', ['void']);
            });

        $totalRevenue = (float) (clone $query)->sum('t.total_amount');
        $totalCount   = (int) (clone $query)->count();

        // Cari menu yang paling banyak terjual pada periode yang sama
        $dominantItemName = DB::table('transaction_details as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->join('menus', 'menus.id', '=', 'td.menu_id')
            ->where('t.user_id', $userId)
            ->when(! empty($selectedDate), function ($q) use ($selectedDate) {
                $start = Carbon::parse($selectedDate)->startOfDay();
                $end   = Carbon::parse($selectedDate)->endOfDay();
                $q->whereBetween('t.created_at', [$start, $end]);
            })
            ->where(function ($query) {
                $query->whereNull('t.status')
                    ->orWhereRaw('LOWER(t.status) <> ?', ['void']);
            })
            ->select('menus.name', DB::raw('SUM(td.quantity) as total_qty'))
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('total_qty')
            ->first()
            ?->name;

        $targetRevenue = 0.0;
        $targetTransactions = 0;

        if (Schema::hasTable('daily_targets')) {
            $targetQuery = DailyTarget::query()
                ->whereDate('target_date', '<=', $selectedDate)
                ->orderByDesc('target_date');

            if (Schema::hasColumn('daily_targets', 'branch_id')) {
                $cashier = User::query()->find($userId, ['id', 'branch_id']);
                $branchId = BranchScope::userBranchId($cashier);

                if ($branchId) {
                    $targetQuery->where('branch_id', $branchId);
                }
            }

            $target = $targetQuery->first(['target_revenue', 'target_transactions']);

            $targetRevenue = (float) ($target->target_revenue ?? 0);
            $targetTransactions = (int) ($target->target_transactions ?? 0);
        }

        $revenueAchievedPct = $targetRevenue > 0
            ? round(($totalRevenue / $targetRevenue) * 100, 1)
            : 0.0;

        $transactionAchievedPct = $targetTransactions > 0
            ? round(($totalCount / $targetTransactions) * 100, 1)
            : 0.0;

        return [
            'date'               => $selectedDate,
            'total_revenue'      => $totalRevenue,
            'total_count'        => $totalCount,
            'dominant_item_name' => $dominantItemName,
            'target_revenue'     => $targetRevenue,
            'target_count'       => $targetTransactions,
            'target_achieved_pct' => $revenueAchievedPct,
            'target_count_achieved_pct' => $transactionAchievedPct,
            // Backward-compatible aliases for older mobile clients.
            'target_harian' => $targetRevenue,
            'target_transactions' => $targetTransactions,
            'target_percentage' => $revenueAchievedPct,
            'target_progress_percent' => $revenueAchievedPct,
            'achievement_percentage' => $revenueAchievedPct,
            'transaction_target_percentage' => $transactionAchievedPct,
            'target' => [
                'revenue' => $targetRevenue,
                'transactions' => $targetTransactions,
                'revenue_achieved_pct' => $revenueAchievedPct,
                'transactions_achieved_pct' => $transactionAchievedPct,
            ],
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
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['void']);
            })
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

    public function buildCheckoutDraft(array $validated, int $cashierId): array
    {
        $lineItems = [];
        $totalAmount = 0.0;

        $paymentMethod = PaymentMethod::query()
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->findOrFail((int) $validated['payment_method_id']);

        $requestedVariantIds = collect($validated['items'])
            ->pluck('variant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $variants = MenuVariant::query()
            ->with([
                'menu:id,name,is_active',
                'ingredients:id,name',
            ])
            ->whereIn('id', $requestedVariantIds)
            ->get()
            ->keyBy('id');

        foreach ($validated['items'] as $item) {
            $variantId = (int) $item['variant_id'];
            $variant = $variants->get($variantId);

            if (! $variant) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                    ->setModel(MenuVariant::class, [$variantId]);
            }

            if (! $variant->is_available || ! optional($variant->menu)->is_active) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => "Variant '{$variant->name}' tidak tersedia untuk dijual.",
                ];
            }

            $qty = (int) $item['qty'];
            $availability = $this->variantAvailabilityService->evaluateSingleForCheckout(
                $variant,
                $cashierId,
                $qty
            );

            if (! ($availability['is_available'] ?? false)) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => "Variant '{$variant->name}' tidak tersedia untuk dijual.",
                    'data' => [
                        'variant_id' => $variantId,
                        'variant_name' => $variant->name,
                        'unavailable_reason' => $availability['unavailable_reason'] ?? null,
                        'required_ingredients' => $availability['required_ingredients'] ?? [],
                    ],
                ];
            }

            $price = (float) $variant->price;

            $subtotal = $price * $qty;
            $totalAmount += $subtotal;

            $lineItems[] = [
                'variant_id' => $variantId,
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
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SET LOCAL lock_timeout = '5s'");
                DB::statement("SET LOCAL statement_timeout = '12s'");
            }

            $now = now(config('app.timezone', 'Asia/Jakarta'));
            $cashier = User::query()->with('branch:id,name,code')->find($userId);
            $branchId = BranchScope::userBranchId($cashier);
            $branch = $cashier?->branch ?: Branch::query()->find($branchId, ['id', 'name', 'code']);

            if (! $branchId || ! $branch) {
                throw new RuntimeException('Cabang kasir tidak ditemukan. Transaksi tidak dapat diproses.');
            }

            $transactionCode = $this->generateTransactionCode($now, $branchId, (string) $branch->code);

            // Cari sesi kasir yang aktif hari ini untuk menautkan transaksi ke sesi
            $activeSessionId = \App\Models\DailyStockSession::query()
                ->where('cashier_id', $userId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereDate('session_date', $now->toDateString())
                ->whereRaw("LOWER(TRIM(status)) = 'open'")
                ->value('id');

            $transactionId = DB::table('transactions')->insertGetId([
                'transaction_code' => $transactionCode,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'total_amount' => $draft['total_amount'],
                'payment_method_id' => (int) $draft['payment_method']->id,
                'paid_amount' => $draft['paid_amount'],
                'change_amount' => $draft['paid_amount'] - $draft['total_amount'],
                'status' => 'SUCCESS',
                'daily_stock_session_id' => $activeSessionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($draft['line_items'] as $line) {
                DB::table('transaction_details')->insert([
                    'transaction_id' => $transactionId,
                    'menu_id' => $line['menu_id'],
                    'menu_variant_id' => $line['variant_id'],
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
                    $note,
                    $userId,
                    $now,
                    $branchId
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
            $start = Carbon::parse($date)->startOfDay();
            $end = Carbon::parse($date)->endOfDay();
            // Use range query so index (user_id, created_at) can be utilized.
            $query->whereBetween('t.created_at', [$start, $end]);
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

    private function generateTransactionCode(Carbon $now, int $branchId, string $branchCode): string
    {
        $sequenceDate = $now->toDateString();
        $timestamp = $now->toDateTimeString();

        DB::table('transaction_sequences')->insertOrIgnore([
            'branch_id' => $branchId,
            'sequence_date' => $sequenceDate,
            'last_number' => 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $sequence = DB::table('transaction_sequences')
            ->where('branch_id', $branchId)
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        $nextNumber = ((int) ($sequence->last_number ?? 0)) + 1;

        DB::table('transaction_sequences')
            ->where('branch_id', $branchId)
            ->where('sequence_date', $sequenceDate)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => $timestamp,
            ]);

        return sprintf(
            'TRX-%s-%s-%03d',
            $this->transactionBranchCode($branchCode),
            $now->format('Ymd'),
            $nextNumber
        );
    }

    private function transactionBranchCode(string $branchCode): string
    {
        $code = strtoupper(Str::slug($branchCode));

        return $code !== '' ? $code : 'CABANG';
    }
}
