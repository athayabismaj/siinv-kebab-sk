<?php

namespace Tests\Integration;

use App\Actions\Sales\CheckoutTransactionAction;
use App\Models\Branch;
use App\Models\DailySalesSummary;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\Phase5bPostgreSqlDatabase;
use Tests\TestCase;

class PostgreSqlConcurrencyTest extends TestCase
{
    private static ?Phase5bPostgreSqlDatabase $database = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$database === null) {
            self::$database = Phase5bPostgreSqlDatabase::fromEnvironment();
            self::$database->createAndMigrate();
        } else {
            self::$database->configureAsDefault();
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            self::$database?->drop();
        } finally {
            self::$database = null;
            parent::tearDownAfterClass();
        }
    }

    public function test_postgresql_constraints_reject_duplicate_sequences_summaries_and_invalid_stock_types(): void
    {
        $context = $this->seedCheckoutContext();

        Transaction::query()->create([
            'transaction_code' => 'TRX-F5B-CONSTRAINT',
            'branch_id' => $context['branch']->id,
            'user_id' => $context['cashier']->id,
            'total_amount' => 10000,
            'payment_method_id' => $context['payment']->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
            'daily_stock_session_id' => $context['session']->id,
        ]);

        $this->assertDatabaseRejects(fn () => Transaction::query()->create([
            'transaction_code' => 'TRX-F5B-CONSTRAINT',
            'branch_id' => $context['branch']->id,
            'user_id' => $context['cashier']->id,
            'total_amount' => 10000,
            'payment_method_id' => $context['payment']->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
            'daily_stock_session_id' => $context['session']->id,
        ]));

        DB::table('transaction_sequences')->insert([
            'branch_id' => $context['branch']->id,
            'sequence_date' => now()->toDateString(),
            'last_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseRejects(fn () => DB::table('transaction_sequences')->insert([
            'branch_id' => $context['branch']->id,
            'sequence_date' => now()->toDateString(),
            'last_number' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        DailySalesSummary::query()->create([
            'branch_id' => $context['branch']->id,
            'sale_date' => now()->toDateString(),
            'total_transactions' => 1,
            'total_revenue' => 10000,
            'total_items_sold' => 1,
        ]);
        $this->assertDatabaseRejects(fn () => DailySalesSummary::query()->create([
            'branch_id' => $context['branch']->id,
            'sale_date' => now()->toDateString(),
            'total_transactions' => 2,
            'total_revenue' => 20000,
            'total_items_sold' => 2,
        ]));

        $this->assertDatabaseRejects(fn () => DB::table('stock_logs')->insert([
            'branch_id' => $context['branch']->id,
            'ingredient_id' => $context['ingredient']->id,
            'type' => 'not-a-valid-stock-type',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_two_real_processes_cannot_oversell_daily_stock(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 1);
        $results = $this->runWorkers('checkout', [
            $this->checkoutPayload($context),
            $this->checkoutPayload($context),
        ]);

        $successful = collect($results)->filter(fn (array $result) => $result['ok'] === true && ($result['result']['ok'] ?? false) === true);

        $this->assertCount(1, $successful, json_encode($results));
        $this->assertSame(0.0, (float) $context['item']->fresh()->remaining_qty);
        $this->assertSame(1, Transaction::query()->where('branch_id', $context['branch']->id)->where('status', 'SUCCESS')->count());
        $this->assertSame(1, StockLog::query()->where('type', 'daily_usage')->count());
        $this->assertSame(1, DailySalesSummary::query()->where('branch_id', $context['branch']->id)->whereDate('sale_date', now())->count());
    }

    public function test_two_real_processes_keep_transfer_stock_non_negative(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 0, warehouseStock: 1);
        $results = $this->runWorkers('transfer', [
            $this->transferPayload($context),
            $this->transferPayload($context),
        ]);

        $this->assertCount(2, $results);
        $this->assertSame(0.0, (float) $context['ingredient']->fresh()->stock);
        $this->assertSame(1.0, (float) $context['item']->fresh()->remaining_qty);
        $this->assertSame(1, StockLog::query()->where('type', 'transfer_daily')->count());
    }

    public function test_concurrent_summary_rebuild_keeps_one_branch_date_row(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 4);
        $checkout = app(CheckoutTransactionAction::class)->execute($this->checkoutRequest($context), $context['cashier']->id);
        $this->assertTrue($checkout['ok']);

        $results = $this->runWorkers('rebuild-summary', [
            ['branch_id' => $context['branch']->id],
            ['branch_id' => $context['branch']->id],
        ]);

        $this->assertCount(2, $results);
        $this->assertSame(1, DailySalesSummary::query()->where('branch_id', $context['branch']->id)->whereDate('sale_date', now())->count());
    }

    public function test_duplicate_void_from_two_processes_creates_one_return_and_one_refund_entry(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 2);
        $checkout = app(CheckoutTransactionAction::class)->execute($this->checkoutRequest($context), $context['cashier']->id);
        $this->assertTrue($checkout['ok']);

        $transactionId = (int) $checkout['result']['transaction_id'];
        $results = $this->runWorkers('void', [
            $this->voidPayload($context, $transactionId, 'one'),
            $this->voidPayload($context, $transactionId, 'two'),
        ]);

        $this->assertCount(1, collect($results)->where('ok', true));
        $this->assertSame('VOID', Transaction::query()->findOrFail($transactionId)->status);
        $this->assertSame(2.0, (float) $context['item']->fresh()->remaining_qty);
        $this->assertSame(1, StockLog::query()->where('type', 'daily_return')->where('reference_id', $transactionId)->count());
        $this->assertSame(1, DB::table('cashflow_entries')->where('source', 'Transaction Void')->count());
    }

    public function test_concurrent_restock_preserves_both_inventory_changes(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 0, warehouseStock: 0);
        $results = $this->runWorkers('restock', [
            [
                'ingredient_id' => $context['ingredient']->id,
                'quantity' => 1,
                'branch_id' => $context['branch']->id,
            ],
            [
                'ingredient_id' => $context['ingredient']->id,
                'quantity' => 1,
                'branch_id' => $context['branch']->id,
            ],
        ]);

        $this->assertCount(2, collect($results)->where('ok', true));
        $this->assertSame(2.0, (float) $context['ingredient']->fresh()->stock);
        $this->assertSame(2, StockLog::query()->where('type', 'in')->count());
    }

    public function test_concurrent_close_session_returns_inventory_only_once(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 1, warehouseStock: 0);
        $results = $this->runWorkers('close-session', [
            $this->closePayload($context),
            $this->closePayload($context),
        ]);

        $this->assertCount(1, collect($results)->where('ok', true));
        $this->assertSame('closed', $context['session']->fresh()->status);
        $this->assertSame(1.0, (float) $context['ingredient']->fresh()->stock);
        $this->assertSame(1, StockLog::query()->where('type', 'daily_return')->where('reference_id', $context['session']->id)->count());
    }

    public function test_two_real_processes_open_one_daily_session_for_the_same_cashier_and_date(): void
    {
        $context = $this->seedCheckoutContext();
        $context['item']->delete();
        $context['session']->delete();

        $payload = [
            'session_date' => now()->toDateString(),
            'cashier_id' => $context['cashier']->id,
            'opened_by' => $context['cashier']->id,
            'branch_id' => $context['branch']->id,
        ];
        $dailyStockItemCount = DailyStockItem::query()->count();
        $stockLogCount = StockLog::query()->count();
        $results = $this->runWorkers('open-session', [$payload, $payload]);

        $this->assertCount(2, collect($results)->where('ok', true), json_encode($results));
        $this->assertCount(1, collect($results)->pluck('result.id')->unique(), json_encode($results));
        $this->assertSame(1, DailyStockSession::query()
            ->where('cashier_id', $context['cashier']->id)
            ->whereDate('session_date', now())
            ->count());
        $this->assertSame($dailyStockItemCount, DailyStockItem::query()->count());
        $this->assertSame($stockLogCount, StockLog::query()->count());
    }

    public function test_concurrent_adjustment_keeps_inventory_at_a_valid_explicit_value(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 0, warehouseStock: 2);
        $results = $this->runWorkers('adjust', [
            [
                'ingredient_id' => $context['ingredient']->id,
                'new_stock' => 5,
                'branch_id' => $context['branch']->id,
            ],
            [
                'ingredient_id' => $context['ingredient']->id,
                'new_stock' => 7,
                'branch_id' => $context['branch']->id,
            ],
        ]);

        $this->assertCount(2, collect($results)->where('ok', true));
        $this->assertContains((float) $context['ingredient']->fresh()->stock, [5.0, 7.0]);
        $this->assertSame(2, StockLog::query()->where('type', 'adjustment')->count());
    }

    public function test_two_cashiers_with_reversed_recipe_order_can_checkout_without_a_lock_conflict(): void
    {
        $context = $this->seedMultiCashierRecipeContext();
        $results = $this->runWorkers('checkout', [
            $this->checkoutPayload($context['first']),
            $this->checkoutPayload($context['second']),
        ]);

        $this->assertCount(2, collect($results)->filter(fn (array $result) => $result['ok'] === true && ($result['result']['ok'] ?? false) === true), json_encode($results));
        $this->assertSame(0.0, (float) $context['first']['item']->fresh()->remaining_qty);
        $this->assertSame(0.0, (float) $context['second']['item']->fresh()->remaining_qty);
    }

    public function test_one_item_checkout_keeps_a_bounded_postgresql_query_count(): void
    {
        $context = $this->seedCheckoutContext(remainingQty: 2);
        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $checkout = app(CheckoutTransactionAction::class)->execute($this->checkoutRequest($context), $context['cashier']->id);

        $this->assertTrue($checkout['ok']);
        $this->assertLessThanOrEqual(35, $queryCount, "PostgreSQL checkout query count regressed to {$queryCount} queries.");
    }

    /** @return array<string, mixed> */
    private function seedCheckoutContext(float $remainingQty = 1, float $warehouseStock = 10): array
    {
        $suffix = strtolower(bin2hex(random_bytes(5)));
        $branch = Branch::query()->create([
            'name' => 'Fase 5B '.$suffix,
            'code' => 'f5b'.$suffix,
            'is_active' => true,
        ]);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::query()->create([
            'name' => 'Kasir '.$suffix,
            'username' => 'f5b_'.$suffix,
            'email' => 'f5b-'.$suffix.'@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
        ]);
        $payment = PaymentMethod::query()->firstOrCreate(['name' => 'Tunai']);
        $category = IngredientCategory::query()->create(['name' => 'Bahan '.$suffix]);
        $ingredient = Ingredient::query()->create([
            'category_id' => $category->id,
            'name' => 'Bahan '.$suffix,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => $warehouseStock,
            'minimum_stock' => 0,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
        $menu = Menu::query()->create(['name' => 'Menu '.$suffix, 'is_active' => true]);
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Varian '.$suffix,
            'price' => 10000,
            'is_available' => true,
        ]);
        $variant->ingredients()->attach($ingredient->id, ['quantity' => 1]);
        $session = DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $item = DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => $remainingQty,
            'remaining_qty' => $remainingQty,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        return compact('branch', 'cashier', 'payment', 'ingredient', 'variant', 'session', 'item');
    }

    /** @return array{first: array<string, mixed>, second: array<string, mixed>} */
    private function seedMultiCashierRecipeContext(): array
    {
        $first = $this->seedCheckoutContext(remainingQty: 1);
        $suffix = strtolower(bin2hex(random_bytes(5)));
        $secondCashier = User::query()->create([
            'name' => 'Kasir Kedua '.$suffix,
            'username' => 'f5b_second_'.$suffix,
            'email' => 'f5b-second-'.$suffix.'@example.test',
            'password' => 'secret123',
            'role_id' => $first['cashier']->role_id,
            'branch_id' => $first['branch']->id,
        ]);
        $secondIngredient = Ingredient::query()->create([
            'category_id' => $first['ingredient']->category_id,
            'name' => 'Bahan Kedua '.$suffix,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 10,
            'minimum_stock' => 0,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
        $first['variant']->ingredients()->attach($secondIngredient->id, ['quantity' => 1]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $first['session']->id,
            'ingredient_id' => $secondIngredient->id,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $secondMenu = Menu::query()->create(['name' => 'Menu Kedua '.$suffix, 'is_active' => true]);
        $secondVariant = MenuVariant::query()->create([
            'menu_id' => $secondMenu->id,
            'name' => 'Varian Kedua '.$suffix,
            'price' => 10000,
            'is_available' => true,
        ]);
        $secondVariant->ingredients()->attach($secondIngredient->id, ['quantity' => 1]);
        $secondVariant->ingredients()->attach($first['ingredient']->id, ['quantity' => 1]);

        $secondSession = DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $first['branch']->id,
            'cashier_id' => $secondCashier->id,
            'opened_by' => $secondCashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $secondItem = DailyStockItem::query()->create([
            'daily_stock_session_id' => $secondSession->id,
            'ingredient_id' => $first['ingredient']->id,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $secondSession->id,
            'ingredient_id' => $secondIngredient->id,
            'opening_qty' => 1,
            'remaining_qty' => 1,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        return [
            'first' => $first,
            'second' => [
                'branch' => $first['branch'],
                'cashier' => $secondCashier,
                'payment' => $first['payment'],
                'ingredient' => $first['ingredient'],
                'variant' => $secondVariant,
                'session' => $secondSession,
                'item' => $secondItem,
            ],
        ];
    }

    /** @param array<string, mixed> $context @return array<string, int|float> */
    private function checkoutPayload(array $context): array
    {
        return [
            'cashier_id' => $context['cashier']->id,
            'payment_method_id' => $context['payment']->id,
            'variant_id' => $context['variant']->id,
            'qty' => 1,
            'paid_amount' => 10000,
        ];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function checkoutRequest(array $context): array
    {
        return [
            'payment_method_id' => $context['payment']->id,
            'paid_amount' => 10000,
            'items' => [[
                'variant_id' => $context['variant']->id,
                'qty' => 1,
            ]],
        ];
    }

    /** @param array<string, mixed> $context @return array<string, int|float> */
    private function transferPayload(array $context): array
    {
        return [
            'session_id' => $context['session']->id,
            'ingredient_id' => $context['ingredient']->id,
            'quantity' => 1,
            'actor_id' => $context['cashier']->id,
            'branch_id' => $context['branch']->id,
        ];
    }

    /** @param array<string, mixed> $context @return array<string, int|string> */
    private function voidPayload(array $context, int $transactionId, string $suffix): array
    {
        return [
            'transaction_id' => $transactionId,
            'session_id' => $context['session']->id,
            'actor_id' => $context['cashier']->id,
            'idempotency_key' => 'phase5b-'.$suffix.'-'.$transactionId,
        ];
    }

    /** @param array<string, mixed> $context @return array<string, int|float> */
    private function closePayload(array $context): array
    {
        return [
            'session_id' => $context['session']->id,
            'ingredient_id' => $context['ingredient']->id,
            'remaining_qty' => 1,
            'actor_id' => $context['cashier']->id,
            'branch_id' => $context['branch']->id,
        ];
    }

    /** @param array<int, array<string, mixed>> $payloads @return array<int, array<string, mixed>> */
    private function runWorkers(string $scenario, array $payloads): array
    {
        $environment = self::$database->workerEnvironment();
        $workers = collect($payloads)->map(function (array $payload) use ($scenario, $environment) {
            $worker = new Process([
                PHP_BINARY,
                base_path('tests/Support/Phase5bPostgreSqlWorker.php'),
                $scenario,
                base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
            ], base_path(), $environment);
            $worker->setTimeout(30);
            $worker->start();

            return $worker;
        });

        return $workers->map(function (Process $worker): array {
            $worker->wait();
            $output = trim($worker->getOutput());
            $this->assertNotSame('', $output, $worker->getErrorOutput());

            return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        })->all();
    }

    private function assertDatabaseRejects(callable $callback): void
    {
        try {
            $callback();
            $this->fail('PostgreSQL accepted a value that should violate a database constraint.');
        } catch (QueryException $exception) {
            $this->assertNotSame('', (string) ($exception->errorInfo[0] ?? ''));
        }
    }
}
