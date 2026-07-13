<?php

namespace Tests\Feature\Reporting;

use App\Models\Branch;
use App\Models\DailyStockSession;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\DailyStockReportQueryService;
use App\Services\Owner\DashboardQueryService;
use App\Services\Owner\SalesReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardReportingQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_only_counts_successful_transactions_from_the_active_branch(): void
    {
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => $branchA->id,
        ]);
        $cashierA = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $branchA->id,
        ]);
        $cashierB = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $branchB->id,
        ]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);

        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'SUCCESS', 10000);
        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'VOID', 2500);
        $this->createTransaction($branchB, $cashierB, $paymentMethod, 'SUCCESS', 40000);
        Cache::flush();

        $response = $this->actingAs($admin)->get(route('admin.panel'));

        $response->assertOk()
            ->assertViewHas('transactionsTodayCount', 1)
            ->assertViewHas('salesLast7Days', function ($salesLast7Days): bool {
                return (float) $salesLast7Days->sum('omzet') === 10000.0;
            });
    }

    public function test_owner_sales_report_metrics_are_branch_scoped_and_exclude_void_transactions(): void
    {
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Pati',
            'code' => 'pti',
            'is_active' => true,
        ]);
        $ownerRole = Role::query()->firstOrCreate(['name' => 'owner']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'branch_id' => $branchA->id,
        ]);
        $cashierA = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchA->id]);
        $cashierB = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchB->id]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);

        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'SUCCESS', 10000);
        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'VOID', 2500);
        $this->createTransaction($branchB, $cashierB, $paymentMethod, 'SUCCESS', 40000);
        Cache::flush();

        $service = app(SalesReportQueryService::class);
        $selectedDate = now()->startOfDay();

        $branchSummary = $this->actingAs($owner)
            ->withSession(['owner_active_branch_id' => $branchA->id])
            ->get(route('owner.panel'))
            ->assertOk()
            ->viewData('todayRevenue');

        $this->assertSame(10000.0, (float) $branchSummary);
        $this->assertSame(1, $service->buildDailySummary($selectedDate, $branchA->id)['totalTransactions']);
        $this->assertSame(10000.0, $service->buildDailySummary($selectedDate, $branchA->id)['totalRevenue']);
        $this->assertSame(2, $service->buildDailySummary($selectedDate)['totalTransactions']);
        $this->assertSame(50000.0, $service->buildDailySummary($selectedDate)['totalRevenue']);
    }

    public function test_daily_stock_report_query_uses_the_explicit_branch_context(): void
    {
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Kudus',
            'code' => 'kds',
            'is_active' => true,
        ]);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashierA = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchA->id]);
        $cashierB = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchB->id]);

        DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branchA->id,
            'cashier_id' => $cashierA->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branchB->id,
            'cashier_id' => $cashierB->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $service = app(DailyStockReportQueryService::class);
        $rows = $service->rows(now()->startOfDay(), now()->endOfDay(), $branchA->id);

        $this->assertCount(1, $rows);
        $this->assertSame($branchA->id, $rows->first()->branch_id);
    }

    public function test_owner_dashboard_query_uses_the_explicit_branch_context(): void
    {
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Pati',
            'code' => 'pti',
            'is_active' => true,
        ]);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashierA = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchA->id]);
        $cashierB = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchB->id]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);

        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'SUCCESS', 10000);
        $this->createTransaction($branchB, $cashierB, $paymentMethod, 'SUCCESS', 40000);
        Cache::flush();
        app('session')->forget('owner_active_branch_id');

        $data = app(DashboardQueryService::class)->buildDashboardData(
            $branchA->id,
            collect([$branchA]),
        );

        $this->assertSame(10000.0, (float) $data['todayRevenue']);
        $this->assertSame($branchA->id, $data['branchId']);
    }

    public function test_owner_cashflow_cache_isolated_by_the_effective_branch_context(): void
    {
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $ownerRole = Role::query()->firstOrCreate(['name' => 'owner']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $owner = User::factory()->create(['role_id' => $ownerRole->id, 'branch_id' => $branchA->id]);
        $cashierA = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchA->id]);
        $cashierB = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branchB->id]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Cash']);

        $this->createTransaction($branchA, $cashierA, $paymentMethod, 'SUCCESS', 10000);
        $this->createTransaction($branchB, $cashierB, $paymentMethod, 'SUCCESS', 40000);
        Cache::flush();

        $branchAResponse = $this->actingAs($owner)
            ->withSession(['owner_active_branch_id' => $branchA->id])
            ->get(route('owner.reports.cashflow'));
        $branchBResponse = $this->actingAs($owner)
            ->withSession(['owner_active_branch_id' => $branchB->id])
            ->get(route('owner.reports.cashflow'));

        $branchAResponse->assertOk()->assertViewHas('salesRevenue', 10000.0);
        $branchBResponse->assertOk()->assertViewHas('salesRevenue', 40000.0);
    }

    private function createTransaction(
        Branch $branch,
        User $cashier,
        PaymentMethod $paymentMethod,
        string $status,
        int $totalAmount,
    ): void {
        Transaction::query()->create([
            'transaction_code' => 'TRX-REPORT-' . strtoupper(uniqid()),
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $totalAmount,
            'change_amount' => 0,
            'status' => $status,
            'created_at' => now()->startOfDay()->addHours(10),
            'updated_at' => now()->startOfDay()->addHours(10),
        ]);
    }
}
