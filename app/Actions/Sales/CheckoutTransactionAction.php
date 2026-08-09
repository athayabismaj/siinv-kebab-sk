<?php

namespace App\Actions\Sales;

use App\DTOs\CashierOperationalContext;
use App\Models\Branch;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Analytics\DailySalesSummaryService;
use App\Services\Api\CashierOperationalContextResolver;
use App\Services\StockService;
use App\Services\VariantAvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                DB::statement("SET LOCAL lock_timeout = '5s'");
                DB::statement("SET LOCAL statement_timeout = '12s'");
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

            $draft = $this->buildCheckoutDraft($validated, $cashierId, $operationalContext);
            if (! $draft['ok']) {
                return $draft;
            }

            $result = $this->createTransaction(
                $cashierId,
                $draft,
                $validated['note'] ?? null,
                $operationalContext,
            );
            $this->dailySalesSummaryService->recordSuccessfulTransaction(
                $result['branch_model'],
                $result['occurred_at'],
                (float) $result['total_amount'],
                (int) $result['total_items_sold'],
            );

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
     * @return array{ok:bool,status?:int,message?:string,data?:array<string,mixed>,payment_method?:PaymentMethod,line_items?:array<int,array<string,mixed>>,ingredient_usages?:array<int,array<string,mixed>>,total_amount?:float,paid_amount?:float}
     */
    private function buildCheckoutDraft(
        array $validated,
        int $cashierId,
        CashierOperationalContext $operationalContext,
    ): array {
        $lineItems = [];
        $ingredientUsages = [];
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
            'ingredient_usages' => array_values($ingredientUsages),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ];
    }

    /**
     * @param  array{payment_method:PaymentMethod,line_items:array<int,array<string,mixed>>,ingredient_usages:array<int,array<string,mixed>>,total_amount:float,paid_amount:float}  $draft
     * @return array<string,mixed>
     */
    private function createTransaction(
        int $cashierId,
        array $draft,
        ?string $note,
        CashierOperationalContext $operationalContext,
    ): array {
        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $branchId = $operationalContext->operationalBranchId();
        $branch = $branchId ? Branch::query()->find($branchId, ['id', 'name', 'code', 'address']) : null;

        if (! $branchId || ! $branch || ! $operationalContext->session) {
            throw new RuntimeException('Sesi stok harian kasir belum dibuka. Transaksi tidak dapat diproses.');
        }

        $transactionCode = $this->generateTransactionCode($now, $branchId, (string) $branch->code);
        $activeSessionId = $operationalContext->sessionId();

        $transactionId = DB::table('transactions')->insertGetId([
            'transaction_code' => $transactionCode,
            'branch_id' => $branchId,
            'user_id' => $cashierId,
            'total_amount' => $draft['total_amount'],
            'payment_method_id' => (int) $draft['payment_method']->id,
            'paid_amount' => $draft['paid_amount'],
            'change_amount' => $draft['paid_amount'] - $draft['total_amount'],
            'status' => 'SUCCESS',
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
        );

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
