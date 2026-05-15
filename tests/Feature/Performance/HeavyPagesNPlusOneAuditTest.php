<?php

namespace Tests\Feature\Performance;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HeavyPagesNPlusOneAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_stock_logs_query_growth_is_flat(): void
    {
        [$admin, $cashier, $ingredient] = $this->seedBaseActorsAndIngredient();

        $this->seedStockLogs($ingredient->id, 5);
        $smallCount = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.stocks.logs', [
            'period' => 'daily',
            'date' => now()->toDateString(),
        ]))->assertOk());

        StockLog::query()->delete();

        $this->seedStockLogs($ingredient->id, 40);
        $largeCount = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.stocks.logs', [
            'period' => 'daily',
            'date' => now()->toDateString(),
        ]))->assertOk());

        $this->assertLessThanOrEqual(
            6,
            $largeCount - $smallCount,
            'Query growth on admin stock logs indicates potential N+1 regression.'
        );
    }

    public function test_admin_daily_stock_report_query_growth_is_flat(): void
    {
        [$admin, $cashier, $ingredient] = $this->seedBaseActorsAndIngredient();

        $this->seedDailyStockSessions($cashier->id, $ingredient->id, 3);
        $smallCount = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.reports.daily-stock', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());

        DailyStockItem::query()->delete();
        DailyStockSession::query()->delete();

        $this->seedDailyStockSessions($cashier->id, $ingredient->id, 25);
        $largeCount = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.reports.daily-stock', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());

        $this->assertLessThanOrEqual(
            6,
            $largeCount - $smallCount,
            'Query growth on daily stock report indicates potential N+1 regression.'
        );
    }

    public function test_transaction_pages_query_growth_is_flat_for_admin_and_owner(): void
    {
        [$admin, $cashier, $ingredient, $owner] = $this->seedBaseActorsAndIngredient(withOwner: true);
        $payment = PaymentMethod::create(['name' => 'Tunai']);

        $this->seedTransactions($cashier->id, $payment->id, 5);
        $adminSmall = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.transactions.index', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());
        $ownerSmall = $this->countQueries(fn () => $this->actingAs($owner)->get(route('owner.transactions.index', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());

        Transaction::query()->delete();

        $this->seedTransactions($cashier->id, $payment->id, 60);
        $adminLarge = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.transactions.index', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());
        $ownerLarge = $this->countQueries(fn () => $this->actingAs($owner)->get(route('owner.transactions.index', [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk());

        $this->assertLessThanOrEqual(6, $adminLarge - $adminSmall, 'Admin transaction page query growth indicates potential N+1 regression.');
        $this->assertLessThanOrEqual(6, $ownerLarge - $ownerSmall, 'Owner transaction page query growth indicates potential N+1 regression.');
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(static function () use (&$count) {
            $count++;
        });

        $callback();

        return $count;
    }

    /**
     * @return array{0: User, 1: User, 2: Ingredient, 3?: User}
     */
    private function seedBaseActorsAndIngredient(bool $withOwner = false): array
    {
        $adminRole = Role::create(['name' => 'admin']);
        $cashierRole = Role::create(['name' => 'kasir']);

        $ownerRole = null;
        if ($withOwner) {
            $ownerRole = Role::create(['name' => 'owner']);
        }

        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'email' => 'admin-test@example.test',
            'password' => 'secret123',
            'role_id' => $adminRole->id,
        ]);

        $cashier = User::create([
            'name' => 'Kasir Test',
            'username' => 'kasir_test',
            'email' => 'kasir-test@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
        ]);

        $owner = null;
        if ($ownerRole) {
            $owner = User::create([
                'name' => 'Owner Test',
                'username' => 'owner_test',
                'email' => 'owner-test@example.test',
                'password' => 'secret123',
                'role_id' => $ownerRole->id,
            ]);
        }

        $category = IngredientCategory::create(['name' => 'Bahan Test']);
        $ingredient = Ingredient::create([
            'category_id' => $category->id,
            'name' => 'Sosis',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 1000,
            'minimum_stock' => 10,
            'selling_price' => 10000,
            'cost_price' => 5000,
        ]);

        if ($owner) {
            return [$admin, $cashier, $ingredient, $owner];
        }

        return [$admin, $cashier, $ingredient];
    }

    private function seedStockLogs(int $ingredientId, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            StockLog::create([
                'ingredient_id' => $ingredientId,
                'type' => $i % 3 === 0 ? 'adjustment' : ($i % 2 === 0 ? 'daily_usage' : 'transfer_daily'),
                'quantity' => $i % 2 === 0 ? -1 : -2,
                'reference_id' => $i,
                'note' => 'Audit N+1 ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedDailyStockSessions(int $cashierId, int $ingredientId, int $count): void
    {
        $cashierRoleId = User::query()->findOrFail($cashierId)->role_id;

        for ($i = 1; $i <= $count; $i++) {
            $sessionCashierId = $cashierId;
            if ($i > 1) {
                $suffix = uniqid((string) $i . '_', true);
                $sessionCashierId = User::create([
                    'name' => 'Kasir Report ' . $i,
                    'username' => 'kasir_report_' . $suffix,
                    'email' => 'kasir-report-' . $suffix . '@example.test',
                    'password' => 'secret123',
                    'role_id' => $cashierRoleId,
                ])->id;
            }

            $session = DailyStockSession::create([
                'session_date' => now()->toDateString(),
                'cashier_id' => $sessionCashierId,
                'opened_by' => $sessionCashierId,
                'status' => 'closed',
            ]);

            DailyStockItem::create([
                'daily_stock_session_id' => $session->id,
                'ingredient_id' => $ingredientId,
                'opening_qty' => 10,
                'remaining_qty' => 2,
                'used_qty' => 8,
                'returned_qty' => 2,
            ]);
        }
    }

    private function seedTransactions(int $cashierId, int $paymentMethodId, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Transaction::create([
                'transaction_code' => 'TRX-AUDIT-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'user_id' => $cashierId,
                'total_amount' => 15000,
                'payment_method_id' => $paymentMethodId,
                'paid_amount' => 15000,
                'change_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
