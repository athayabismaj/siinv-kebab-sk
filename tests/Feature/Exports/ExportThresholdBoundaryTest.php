<?php

namespace Tests\Feature\Exports;

use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\DailyStockSession;
use App\Models\GeneratedExport;
use App\Models\Ingredient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportThresholdBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_export_uses_direct_export_at_99_and_100_then_queues_at_101(): void
    {
        Queue::fake();
        $owner = $this->owner('transactions');
        $branch = $this->defaultBranch();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        $cashier = $this->cashier($branch, 'transactions');

        $this->transactions($branch, $cashier, $payment, 99, 'TX');
        $this->transactionRequest($owner, $branch, 'html')->assertOk();

        $this->transactions($branch, $cashier, $payment, 1, 'TX');
        $this->transactionRequest($owner, $branch, 'html')->assertOk();

        $this->transactions($branch, $cashier, $payment, 1, 'TX');
        $this->transactionRequest($owner, $branch, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'transaction_history', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    public function test_stock_log_export_uses_direct_export_at_99_and_100_then_queues_at_101(): void
    {
        Queue::fake();
        $owner = $this->owner('stock-logs');
        $branch = $this->defaultBranch();
        $ingredient = Ingredient::query()->create(['name' => 'Bahan Log', 'stock' => 100, 'minimum_stock' => 1, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);

        $this->stockLogs($branch, $ingredient, 99, 'SL');
        $this->stockLogRequest($owner, $branch, 'html')->assertOk();

        $this->stockLogs($branch, $ingredient, 1, 'SL');
        $this->stockLogRequest($owner, $branch, 'html')->assertOk();

        $this->stockLogs($branch, $ingredient, 1, 'SL');
        $this->stockLogRequest($owner, $branch, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'stock_log', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    public function test_usage_export_uses_direct_export_at_99_and_100_then_queues_at_101(): void
    {
        Queue::fake();
        $owner = $this->owner('usage');
        $branch = $this->defaultBranch();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        $cashier = $this->cashier($branch, 'usage');

        $this->usageRows($branch, $cashier, $payment, 99, 'US');
        $this->usageRequest($owner, $branch, 'html')->assertOk();

        $this->usageRows($branch, $cashier, $payment, 1, 'US');
        $this->usageRequest($owner, $branch, 'html')->assertOk();

        $this->usageRows($branch, $cashier, $payment, 1, 'US');
        $this->usageRequest($owner, $branch, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'usage_report', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    public function test_daily_stock_export_uses_direct_export_at_99_and_100_then_queues_at_101(): void
    {
        Queue::fake();
        $branch = $this->defaultBranch();
        $admin = $this->admin($branch, 'daily-stock');

        $this->dailyStockSessions($branch, 99, 'DS');
        $this->dailyStockRequest($admin, 'html')->assertOk();

        $this->dailyStockSessions($branch, 1, 'DS');
        $this->dailyStockRequest($admin, 'html')->assertOk();

        $this->dailyStockSessions($branch, 1, 'DS');
        $this->dailyStockRequest($admin, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'daily_stock_report', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    public function test_expense_export_uses_direct_export_at_249_and_250_then_queues_at_251(): void
    {
        Queue::fake();
        $owner = $this->owner('expenses');
        $branch = $this->defaultBranch();

        $this->expenses($owner, $branch, 249, 'EX');
        $this->expenseRequest($owner, $branch, 'html')->assertOk();

        $this->expenses($owner, $branch, 1, 'EX');
        $this->expenseRequest($owner, $branch, 'html')->assertOk();

        $this->expenses($owner, $branch, 1, 'EX');
        $this->expenseRequest($owner, $branch, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'expense_report', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    public function test_sales_export_uses_direct_export_at_249_and_250_then_queues_at_251(): void
    {
        Queue::fake();
        $owner = $this->owner('sales');
        $branch = $this->defaultBranch();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        $cashier = $this->cashier($branch, 'sales');

        $this->transactions($branch, $cashier, $payment, 249, 'SA');
        $this->salesRequest($owner, $branch, 'html')->assertOk();

        $this->transactions($branch, $cashier, $payment, 1, 'SA');
        $this->salesRequest($owner, $branch, 'html')->assertOk();

        $this->transactions($branch, $cashier, $payment, 1, 'SA');
        $this->salesRequest($owner, $branch, 'excel')->assertRedirect();
        $this->assertDatabaseHas('generated_exports', ['type' => 'sales_report', 'status' => GeneratedExport::STATUS_PENDING]);
    }

    private function transactionRequest(User $owner, Branch $branch, string $format)
    {
        return $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => $format,
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));
    }

    private function stockLogRequest(User $owner, Branch $branch, string $format)
    {
        return $this->actingAs($owner)->get(route('owner.stock-logs.export', [
            'format' => $format,
            'period' => 'daily',
            'date' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));
    }

    private function usageRequest(User $owner, Branch $branch, string $format)
    {
        return $this->actingAs($owner)->get(route('owner.reports.usage.export', [
            'format' => $format,
            'type' => 'daily',
            'date' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));
    }

    private function dailyStockRequest(User $admin, string $format)
    {
        return $this->actingAs($admin)->get(route('admin.reports.daily-stock.export', [
            'format' => $format,
            'type' => 'daily',
            'date' => now()->toDateString(),
        ]));
    }

    private function expenseRequest(User $owner, Branch $branch, string $format)
    {
        return $this->actingAs($owner)->get(route('owner.reports.cashflow.export', [
            'format' => $format,
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));
    }

    private function salesRequest(User $owner, Branch $branch, string $format)
    {
        return $this->actingAs($owner)->get(route('owner.reports.sales.export', [
            'format' => $format,
            'type' => 'daily',
            'date' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));
    }

    private function transactions(Branch $branch, User $cashier, PaymentMethod $payment, int $count, string $prefix): void
    {
        $start = Transaction::query()->count();
        foreach (range(1, $count) as $number) {
            Transaction::query()->create([
                'transaction_code' => sprintf('TRX-%s-%04d', $prefix, $start + $number),
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'payment_method_id' => $payment->id,
                'total_amount' => 1000,
                'paid_amount' => 1000,
                'change_amount' => 0,
                'status' => 'SUCCESS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function stockLogs(Branch $branch, Ingredient $ingredient, int $count, string $prefix): void
    {
        $start = StockLog::query()->count();
        foreach (range(1, $count) as $number) {
            StockLog::query()->create([
                'branch_id' => $branch->id,
                'ingredient_id' => $ingredient->id,
                'type' => 'in',
                'quantity' => 1,
                'note' => sprintf('%s log %d', $prefix, $start + $number),
            ]);
        }
    }

    private function usageRows(Branch $branch, User $cashier, PaymentMethod $payment, int $count, string $prefix): void
    {
        $start = Ingredient::query()->count();
        foreach (range(1, $count) as $number) {
            $suffix = $start + $number;
            $ingredient = Ingredient::query()->create([
                'name' => sprintf('%s Bahan %d', $prefix, $suffix),
                'stock' => 10,
                'minimum_stock' => 1,
                'base_unit' => 'pcs',
                'display_unit' => 'pcs',
            ]);
            $transaction = Transaction::query()->create([
                'transaction_code' => sprintf('TRX-%s-%04d', $prefix, $suffix),
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'payment_method_id' => $payment->id,
                'total_amount' => 1000,
                'paid_amount' => 1000,
                'change_amount' => 0,
                'status' => 'SUCCESS',
            ]);
            StockLog::query()->create([
                'branch_id' => $branch->id,
                'ingredient_id' => $ingredient->id,
                'reference_id' => $transaction->id,
                'type' => 'daily_usage',
                'quantity' => -1,
            ]);
        }
    }

    private function dailyStockSessions(Branch $branch, int $count, string $prefix): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $start = DailyStockSession::query()->count();
        foreach (range(1, $count) as $number) {
            $suffix = $start + $number;
            $cashier = User::query()->create([
                'name' => sprintf('%s Kasir %d', $prefix, $suffix),
                'username' => sprintf('%s-cashier-%d', strtolower($prefix), $suffix),
                'email' => sprintf('%s-cashier-%d@example.test', strtolower($prefix), $suffix),
                'password' => 'secret123',
                'role_id' => $role->id,
                'branch_id' => $branch->id,
            ]);
            DailyStockSession::query()->create([
                'session_date' => now()->toDateString(),
                'branch_id' => $branch->id,
                'cashier_id' => $cashier->id,
                'opened_by' => $cashier->id,
                'status' => 'open',
            ]);
        }
    }

    private function expenses(User $owner, Branch $branch, int $count, string $prefix): void
    {
        $start = CashflowEntry::query()->count();
        foreach (range(1, $count) as $number) {
            CashflowEntry::query()->create([
                'entry_date' => now()->toDateString(),
                'branch_id' => $branch->id,
                'type' => 'expense',
                'amount' => 1000,
                'source' => sprintf('%s Pengeluaran %d', $prefix, $start + $number),
                'created_by' => $owner->id,
            ]);
        }
    }

    private function defaultBranch(): Branch
    {
        return Branch::query()->where('code', 'default')->firstOrFail();
    }

    private function owner(string $suffix): User
    {
        return $this->user('owner', null, $suffix);
    }

    private function admin(Branch $branch, string $suffix): User
    {
        return $this->user('admin', $branch->id, $suffix);
    }

    private function cashier(Branch $branch, string $suffix): User
    {
        return $this->user('kasir', $branch->id, $suffix);
    }

    private function user(string $role, ?int $branchId, string $suffix): User
    {
        $roleModel = Role::query()->firstOrCreate(['name' => $role]);

        return User::query()->create([
            'name' => ucfirst($role) . ' ' . $suffix,
            'username' => $role . '-' . $suffix,
            'email' => $role . '-' . $suffix . '@example.test',
            'password' => 'secret123',
            'role_id' => $roleModel->id,
            'branch_id' => $branchId,
        ]);
    }
}
