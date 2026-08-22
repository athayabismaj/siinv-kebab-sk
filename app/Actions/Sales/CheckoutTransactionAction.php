<?php

namespace App\Actions\Sales;

use App\DTOs\CashierOperationalContext;
use App\Models\Branch;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Analytics\DailySalesSummaryService;
use App\Services\Api\CashierOperationalContextResolver;
use App\Services\StockService;
use App\Services\VariantAvailabilityService;
use App\Support\AdminCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutTransactionAction
{
    public function __construct(
        private readonly VariantAvailabilityService $variantAvailabilityService,
        private readonly DailySalesSummaryService $dailySalesSummaryService,
        private readonly CashierOperationalContextResolver $operationalContextResolver,
    ) {}

    /**
     * @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>,result?:array<string,mixed>}
     */
    public function execute(array $validated, int|User $cashier): array
    {
        $cashierId = $cashier instanceof User ? (int) $cashier->id : $cashier;

        return DB::transaction(function () use ($validated, $cashierId, $cashier) {
            if (DB::getDriverName() === 'pgsql') {
                DB::selectOne(
                    "SELECT set_config('lock_timeout', '5s', true),
                            set_config('statement_timeout', '12s', true)"
                );
            }

            $cashier = $cashier instanceof User
                ? $cashier
                : User::query()->findOrFail($cashierId);
            $operationalContext = $this->operationalContextResolver->resolve(
                $cashier,
                [
                    'items' => fn ($query) => $query
                        ->select('id', 'daily_stock_session_id', 'ingredient_id', 'remaining_qty', 'used_qty')
                        ->orderBy('ingredient_id')
                        ->lockForUpdate(),
                ],
                lockForUpdate: true,
            );
            if ($operationalContext->ambiguous) {
                return [
                    'ok' => false,
                    'status' => 409,
                    'message' => 'Terdapat konflik sesi aktif. Hubungi admin untuk memeriksa sesi kasir.',
                ];
            }
            if (! $operationalContext->session) {
                return [
                    'ok' => false,
                    'status' => 409,
                    'message' => 'Transaksi gagal diproses. Periksa stok harian dan data transaksi lalu coba lagi.',
                ];
            }

            $references = $this->checkoutReferences(
                $operationalContext->operationalBranchId(),
                (int) $validated['payment_method_id'],
            );
            $draft = $this->buildCheckoutDraft(
                $validated,
                $cashierId,
                $operationalContext,
                $references['payment_method'],
            );
            if (! $draft['ok']) {
                return $draft;
            }

            $result = $this->createTransaction(
                $cashierId,
                $draft,
                $validated['note'] ?? null,
                $operationalContext,
                $references['branch'],
            );
            if ($result['status'] === Transaction::STATUS_SUCCESS) {
                $this->dailySalesSummaryService->recordSuccessfulTransaction(
                    $result['branch_model'],
                    $result['occurred_at'],
                    (float) $result['total_amount'],
                    (int) $result['total_items_sold'],
                );
            }

            unset(
                $result['branch_id'],
                $result['branch_model'],
                $result['occurred_at'],
                $result['total_items_sold'],
            );

            return [
                'ok' => true,
                'result' => $result,
            ];
        });
    }

    /**
     * @param  array{id:int,name:string}  $paymentMethod
     * @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>,payment_method?:array{id:int,name:string},line_items?:array<int,array<string,mixed>>,ingredient_usages?:array<int,array<string,mixed>>,total_amount?:float,paid_amount?:float}
     */
    private function buildCheckoutDraft(
        array $validated,
        int $cashierId,
        CashierOperationalContext $operationalContext,
        array $paymentMethod,
    ): array {
        $lineItems = [];
        $ingredientUsages = [];
        $totalAmount = 0.0;

        $requestedVariantIds = collect($validated['items'])
            ->pluck('variant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $variants = MenuVariant::query()
            ->join('menus', 'menus.id', '=', 'menu_variants.menu_id')
            ->select([
                'menu_variants.*',
                'menus.name as menu_name',
                'menus.is_active as menu_is_active',
                'menus.deleted_at as menu_deleted_at',
            ])
            ->with([
                'ingredients:id,name,base_unit,display_unit',
            ])
            ->whereIn('menu_variants.id', $requestedVariantIds)
            ->get()
            ->keyBy('id');

        foreach ($validated['items'] as $item) {
            $variantId = (int) $item['variant_id'];
            $variant = $variants->get($variantId);

            if (! $variant) {
                throw (new ModelNotFoundException)->setModel(MenuVariant::class, [$variantId]);
            }

            if (! $variant->is_available || ! $variant->menu_is_active || $variant->menu_deleted_at) {
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
                $qty,
                operationalContext: $operationalContext,
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
                'menu_name' => $variant->menu_name,
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $subtotal,
            ];

            foreach ($variant->ingredients as $ingredient) {
                $usedQty = (float) $ingredient->pivot->quantity * $qty;
                if ($usedQty <= 0) {
                    continue;
                }

                $ingredientId = (int) $ingredient->id;
                if (isset($ingredientUsages[$ingredientId])) {
                    $ingredientUsages[$ingredientId]['quantity'] += $usedQty;

                    continue;
                }

                $ingredientUsages[$ingredientId] = [
                    'ingredient_id' => $ingredientId,
                    'name' => (string) $ingredient->name,
                    'base_unit' => (string) ($ingredient->base_unit ?: $ingredient->display_unit),
                    'quantity' => $usedQty,
                ];
            }
        }

        $isQris = strtolower(trim((string) $paymentMethod['name'])) === 'qris';
        $paidAmount = $isQris ? 0.0 : (float) $validated['paid_amount'];
        if (! $isQris && $paidAmount < $totalAmount) {
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
            'ingredient_usages' => array_values($ingredientUsages),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ];
    }

    /**
     * @param  array{payment_method:array{id:int,name:string},line_items:array<int,array<string,mixed>>,ingredient_usages:array<int,array<string,mixed>>,total_amount:float,paid_amount:float}  $draft
     * @param  array{id:int,name:string,code:string,address:?string}  $branchData
     * @return array<string,mixed>
     */
    private function createTransaction(
        int $cashierId,
        array $draft,
        ?string $note,
        CashierOperationalContext $operationalContext,
        array $branchData,
    ): array {
        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $branchId = $operationalContext->operationalBranchId();
        $branch = (new Branch)->forceFill($branchData);
        $branch->exists = true;

        if (! $branchId || (int) $branch->id !== $branchId || ! $operationalContext->session) {
            throw new RuntimeException('Sesi stok harian kasir belum dibuka. Transaksi tidak dapat diproses.');
        }

        $transactionCode = $this->generateTransactionCode($now, $branchId, (string) $branch->code);
        $activeSessionId = $operationalContext->sessionId();
        $isQris = strtolower(trim((string) $draft['payment_method']['name'])) === 'qris';
        $status = $isQris ? Transaction::STATUS_PENDING_PAYMENT : Transaction::STATUS_SUCCESS;
        $changeAmount = $isQris ? 0.0 : $draft['paid_amount'] - $draft['total_amount'];

        $transactionId = DB::table('transactions')->insertGetId([
            'transaction_code' => $transactionCode,
            'branch_id' => $branchId,
            'user_id' => $cashierId,
            'total_amount' => $draft['total_amount'],
            'payment_method_id' => (int) $draft['payment_method']['id'],
            'paid_amount' => $draft['paid_amount'],
            'change_amount' => $changeAmount,
            'status' => $status,
            'daily_stock_session_id' => $activeSessionId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('transaction_details')->insert(array_map(
            fn (array $line): array => [
                'transaction_id' => $transactionId,
                'menu_id' => $line['menu_id'],
                'menu_variant_id' => $line['variant_id'],
                'quantity' => $line['qty'],
                'price' => $line['price'],
                'subtotal' => $line['subtotal'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $draft['line_items'],
        ));

        StockService::deductDailyStockBatch(
            $activeSessionId,
            $draft['ingredient_usages'],
            $transactionId,
            $note,
            $cashierId,
            $branchId,
            $operationalContext->session,
            invalidateCaches: false,
        );

        return [
            'transaction_id' => $transactionId,
            'transaction_code' => $transactionCode,
            'status' => $status,
            'created_at' => $now->toIso8601String(),
            'payment_method' => [
                'id' => $draft['payment_method']['id'],
                'name' => $draft['payment_method']['name'],
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
            'change_amount' => round($changeAmount, 2),
            'branch_id' => $branchId,
            'branch_model' => $branch,
            'branch' => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'address' => $branch->address,
            ],
            'occurred_at' => $now,
            'total_items_sold' => (int) collect($draft['line_items'])->sum('qty'),
        ];
    }

    /**
     * Ambil cabang dan metode pembayaran dalam satu round-trip. Hasilnya aman
     * dicache singkat karena perubahan metode pembayaran sudah memakai versi cache.
     *
     * @return array{branch:array{id:int,name:string,code:string,address:?string},payment_method:array{id:int,name:string}}
     */
    private function checkoutReferences(?int $branchId, int $paymentMethodId): array
    {
        if (($branchId ?? 0) <= 0) {
            throw new RuntimeException('Cabang operasional kasir tidak valid.');
        }

        $load = fn () => DB::table('branches as branch')
            ->crossJoin('payment_methods as payment')
            ->where('branch.id', $branchId)
            ->where('payment.id', $paymentMethodId)
            ->whereNull('payment.deleted_at')
            ->select([
                'branch.id as branch_id',
                'branch.name as branch_name',
                'branch.code as branch_code',
                'branch.address as branch_address',
                'payment.id as payment_method_id',
                'payment.name as payment_method_name',
            ])
            ->first();

        $row = app()->environment('testing')
            ? $load()
            : Cache::remember(
                AdminCache::key(
                    'payment_methods',
                    "checkout:branch:{$branchId}:payment:{$paymentMethodId}",
                ),
                now()->addMinute(),
                $load,
            );

        if (! $row) {
            throw (new ModelNotFoundException)->setModel(PaymentMethod::class, [$paymentMethodId]);
        }

        return [
            'branch' => [
                'id' => (int) $row->branch_id,
                'name' => (string) $row->branch_name,
                'code' => (string) $row->branch_code,
                'address' => $row->branch_address !== null ? (string) $row->branch_address : null,
            ],
            'payment_method' => [
                'id' => (int) $row->payment_method_id,
                'name' => (string) $row->payment_method_name,
            ],
        ];
    }

    private function generateTransactionCode(Carbon $now, int $branchId, string $branchCode): string
    {
        $sequenceDate = $now->toDateString();
        $timestamp = $now->toDateTimeString();

        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            $sequence = DB::selectOne(
                'INSERT INTO transaction_sequences
                    (branch_id, sequence_date, last_number, created_at, updated_at)
                VALUES (?, ?, 1, ?, ?)
                ON CONFLICT (branch_id, sequence_date)
                DO UPDATE SET
                    last_number = transaction_sequences.last_number + 1,
                    updated_at = EXCLUDED.updated_at
                RETURNING last_number',
                [$branchId, $sequenceDate, $timestamp, $timestamp],
            );

            $nextNumber = (int) ($sequence->last_number ?? 1);

            return sprintf(
                'TRX-%s-%s-%03d',
                $this->transactionBranchCode($branchCode),
                $now->format('Ymd'),
                $nextNumber,
            );
        }

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
            $nextNumber,
        );
    }

    private function transactionBranchCode(string $branchCode): string
    {
        $code = strtoupper(Str::slug($branchCode));

        return $code !== '' ? $code : 'CABANG';
    }
}
