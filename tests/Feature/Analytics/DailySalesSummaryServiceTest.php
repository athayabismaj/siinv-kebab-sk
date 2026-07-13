<?php

namespace Tests\Feature\Analytics;

use App\Models\Branch;
use App\Models\DailySalesSummary;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Analytics\DailySalesSummaryService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailySalesSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_excludes_void_transactions_from_sales_totals(): void
    {
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::factory()->create([
            'branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);
        $date = Carbon::parse('2026-07-13 10:00:00', 'Asia/Jakarta');

        $this->createTransaction($branch, $cashier, $paymentMethod, $date, 'SUCCESS', 10000, 'TRX-TEST-001');
        $this->createTransaction($branch, $cashier, $paymentMethod, $date, 'VOID', 2500, 'TRX-TEST-002');

        $summary = app(DailySalesSummaryService::class)->rebuildForDate($branch, $date);

        $this->assertSame(1, $summary['total_transactions']);
        $this->assertSame(10000.0, $summary['total_revenue']);
    }

    public function test_summaries_are_isolated_by_branch_for_the_same_date(): void
    {
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);
        $firstCashier = $this->createCashier($firstBranch);
        $secondCashier = $this->createCashier($secondBranch);
        $date = Carbon::parse('2026-07-13 10:00:00', 'Asia/Jakarta');

        $this->createTransaction($firstBranch, $firstCashier, $paymentMethod, $date, 'SUCCESS', 10000, 'TRX-TEST-101');
        $this->createTransaction($secondBranch, $secondCashier, $paymentMethod, $date, 'SUCCESS', 25000, 'TRX-TEST-102');

        $service = app(DailySalesSummaryService::class);
        $firstSummary = $service->rebuildForDate($firstBranch, $date);
        $secondSummary = $service->rebuildForDate($secondBranch, $date);

        $this->assertSame(1, $firstSummary['total_transactions']);
        $this->assertSame(10000.0, $firstSummary['total_revenue']);
        $this->assertSame(1, $secondSummary['total_transactions']);
        $this->assertSame(25000.0, $secondSummary['total_revenue']);
        $this->assertDatabaseCount('daily_sales_summaries', 2);
        $this->assertDatabaseHas('daily_sales_summaries', [
            'branch_id' => $firstBranch->id,
            'sale_date' => '2026-07-13',
            'total_revenue' => 10000,
        ]);
        $this->assertDatabaseHas('daily_sales_summaries', [
            'branch_id' => $secondBranch->id,
            'sale_date' => '2026-07-13',
            'total_revenue' => 25000,
        ]);
    }

    public function test_branch_and_sale_date_must_be_unique_together(): void
    {
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Kebab SK Pati',
            'code' => 'pti',
            'is_active' => true,
        ]);

        $this->createSummary($firstBranch, '2026-07-13');
        $this->createSummary($secondBranch, '2026-07-13');

        $this->expectException(QueryException::class);
        $this->createSummary($firstBranch, '2026-07-13');
    }

    public function test_rebuild_is_idempotent_and_does_not_change_another_branch(): void
    {
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Kebab SK Kudus',
            'code' => 'kds',
            'is_active' => true,
        ]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);
        $firstCashier = $this->createCashier($firstBranch);
        $secondCashier = $this->createCashier($secondBranch);
        $date = Carbon::parse('2026-07-13 10:00:00', 'Asia/Jakarta');

        $this->createTransaction($firstBranch, $firstCashier, $paymentMethod, $date, 'SUCCESS', 10000, 'TRX-TEST-201');
        $this->createTransaction($secondBranch, $secondCashier, $paymentMethod, $date, 'SUCCESS', 25000, 'TRX-TEST-202');

        $service = app(DailySalesSummaryService::class);
        $service->rebuildForDate($firstBranch, $date);
        $service->rebuildForDate($secondBranch, $date);
        $this->createTransaction($firstBranch, $firstCashier, $paymentMethod, $date, 'SUCCESS', 5000, 'TRX-TEST-203');

        $firstSummary = $service->rebuildForDate($firstBranch, $date);
        $service->rebuildForDate($firstBranch, $date);

        $this->assertSame(2, $firstSummary['total_transactions']);
        $this->assertSame(15000.0, $firstSummary['total_revenue']);
        $this->assertDatabaseCount('daily_sales_summaries', 2);
        $this->assertDatabaseHas('daily_sales_summaries', [
            'branch_id' => $secondBranch->id,
            'sale_date' => '2026-07-13',
            'total_transactions' => 1,
            'total_revenue' => 25000,
        ]);
    }

    public function test_branch_summary_migration_can_roll_back_and_migrate_again(): void
    {
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Kebab SK Demak',
            'code' => 'dmk',
            'is_active' => true,
        ]);
        $date = '2026-07-13';

        $this->createSummary($firstBranch, $date, 1, 10000, 2);
        $this->createSummary($secondBranch, $date, 2, 25000, 3);

        $migration = require database_path('migrations/2026_07_13_010000_scope_daily_sales_summaries_by_branch.php');
        $migration->down();

        $this->assertFalse(Schema::hasColumn('daily_sales_summaries', 'branch_id'));
        $this->assertDatabaseCount('daily_sales_summaries', 1);
        $this->assertSame(3, (int) DB::table('daily_sales_summaries')
            ->whereDate('sale_date', $date)
            ->value('total_transactions'));
        $this->assertSame(35000.0, (float) DB::table('daily_sales_summaries')
            ->whereDate('sale_date', $date)
            ->value('total_revenue'));
        $this->assertSame(5, (int) DB::table('daily_sales_summaries')
            ->whereDate('sale_date', $date)
            ->value('total_items_sold'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('daily_sales_summaries', 'branch_id'));
        $this->assertDatabaseCount('daily_sales_summaries', 0);
    }

    private function createCashier(Branch $branch): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);

        return User::factory()->create([
            'branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);
    }

    private function createSummary(
        Branch $branch,
        string $saleDate,
        int $transactions = 0,
        int $revenue = 0,
        int $itemsSold = 0
    ): void {
        DailySalesSummary::query()->create([
            'branch_id' => $branch->id,
            'sale_date' => $saleDate,
            'total_transactions' => $transactions,
            'total_revenue' => $revenue,
            'total_items_sold' => $itemsSold,
        ]);
    }

    private function createTransaction(
        Branch $branch,
        User $cashier,
        PaymentMethod $paymentMethod,
        Carbon $createdAt,
        string $status,
        int $totalAmount,
        string $transactionCode
    ): void {
        $transaction = Transaction::query()->create([
            'transaction_code' => $transactionCode,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $totalAmount,
            'change_amount' => 0,
            'status' => $status,
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();
    }
}
