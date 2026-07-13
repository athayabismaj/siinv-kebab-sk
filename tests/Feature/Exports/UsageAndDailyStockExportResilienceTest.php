<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateDailyStockReportExport;
use App\Jobs\GenerateUsageReportExport;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\GeneratedExport;
use App\Models\Ingredient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\DailyStockReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UsageAndDailyStockExportResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_usage_export_queues_a_branch_snapshot_and_uses_private_storage(): void
    {
        Queue::fake(); Storage::fake('local');
        $owner = $this->user('owner', null); $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $other = Branch::query()->create(['name' => 'Kebab SK Pati', 'code' => 'pti', 'is_active' => true]);
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        foreach (range(1, 101) as $number) $this->usageLog($branch, $payment, 'SUCCESS', 'A' . $number, $number);
        $this->usageLog($branch, $payment, 'VOID', 'VOID', 999);
        $this->usageLog($other, $payment, 'SUCCESS', 'OTHER', 1000);

        $this->actingAs($owner)->get(route('owner.reports.usage.export', ['format'=>'excel','type'=>'daily','date'=>now()->toDateString(),'branch_id'=>$branch->id]))->assertRedirect();
        $export = GeneratedExport::query()->sole();
        $this->assertSame('usage_report', $export->type); $this->assertSame($branch->id, $export->branch_id);
        $this->assertSame(now()->toDateString(), $export->filters['date_from']);
        Queue::assertPushed(GenerateUsageReportExport::class, fn ($job) => $job->generatedExportId === $export->id);

        (new GenerateUsageReportExport($export->id))->handle(app(\App\Services\Exports\UsageReportExportQuery::class));
        $export->refresh(); $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);
        $otherOwner = $this->user('owner', null, 'other-owner');
        $this->actingAs($otherOwner)->get(route('owner.generated-exports.download', $export))->assertForbidden();
    }

    public function test_large_daily_stock_export_queues_branch_snapshot_and_preserves_report_formula(): void
    {
        Queue::fake(); Storage::fake('local');
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $otherBranch = Branch::query()->create(['name' => 'Kebab SK Pati', 'code' => 'pti', 'is_active' => true]);
        $admin = $this->user('admin', $branch->id); $ingredient = Ingredient::query()->create(['name'=>'Roti','stock'=>100,'minimum_stock'=>1,'base_unit'=>'pcs','display_unit'=>'pcs','pack_size'=>1,'cost_price'=>1000,'selling_price'=>2000]);
        $cashierRole = Role::query()->firstOrCreate(['name'=>'kasir']);
        foreach (range(1, 101) as $number) {
            $cashier = User::query()->create(['name'=>'Kasir '.$number,'username'=>'daily-'.$number,'email'=>'daily-'.$number.'@test.local','password'=>'secret123','role_id'=>$cashierRole->id,'branch_id'=>$branch->id]);
            $session = DailyStockSession::query()->create(['session_date'=>now()->toDateString(),'branch_id'=>$branch->id,'cashier_id'=>$cashier->id,'status'=>'closed','opened_at'=>now(),'closed_at'=>now()]);
            DailyStockItem::query()->create(['daily_stock_session_id'=>$session->id,'ingredient_id'=>$ingredient->id,'opening_qty'=>10,'remaining_qty'=>8,'used_qty'=>2]);
        }
        $otherCashier = User::query()->create(['name'=>'Kasir Pati','username'=>'daily-other','email'=>'daily-other@test.local','password'=>'secret123','role_id'=>$cashierRole->id,'branch_id'=>$otherBranch->id]);
        $otherSession = DailyStockSession::query()->create(['session_date'=>now()->toDateString(),'branch_id'=>$otherBranch->id,'cashier_id'=>$otherCashier->id,'status'=>'closed','opened_at'=>now(),'closed_at'=>now()]);
        DailyStockItem::query()->create(['daily_stock_session_id'=>$otherSession->id,'ingredient_id'=>$ingredient->id,'opening_qty'=>10,'remaining_qty'=>8,'used_qty'=>2]);
        $this->actingAs($admin)->get(route('admin.reports.daily-stock.export', ['format'=>'excel','type'=>'daily','date'=>now()->toDateString()]))->assertRedirect();
        $export=GeneratedExport::query()->sole(); $this->assertSame('daily_stock_report',$export->type); $this->assertSame($branch->id,$export->branch_id);
        Queue::assertPushed(GenerateDailyStockReportExport::class, fn ($job) => $job->generatedExportId === $export->id);
        Cache::flush();
        $summary=app(DailyStockReportQueryService::class)->summary(now()->startOfDay(),now()->endOfDay(),$branch->id);
        $this->assertSame(202000.0,(float)$summary['total_value']); $this->assertSame(404000.0,(float)$summary['total_revenue']);
        (new GenerateDailyStockReportExport($export->id))->handle(app(DailyStockReportQueryService::class)); $export->refresh(); Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_failed_daily_stock_export_can_be_retried_without_leaving_a_file_behind(): void
    {
        Queue::fake(); Storage::fake('local');
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $admin = $this->user('admin', $branch->id);
        $export = GeneratedExport::query()->create([
            'requested_by' => $admin->id,
            'branch_id' => $branch->id,
            'type' => 'daily_stock_report',
            'format' => 'excel',
            'filters' => ['type' => 'daily', 'date_from' => 'not-a-date', 'date_to' => 'not-a-date'],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'Laporan_Stok_Harian.xlsx',
            'expires_at' => now()->addDays(7),
        ]);

        try {
            (new GenerateDailyStockReportExport($export->id))->handle(app(DailyStockReportQueryService::class));
            $this->fail('Job ekspor dengan periode tidak valid seharusnya gagal.');
        } catch (\Throwable) {
            // Job sengaja melempar ulang exception agar mekanisme retry queue tetap berjalan.
        }

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_FAILED, $export->status);
        $this->assertNull($export->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles('exports/' . $admin->id . '/' . $export->id));

        $export->forceFill([
            'filters' => ['type' => 'daily', 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()],
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.generated-exports.retry', $export))
            ->assertRedirect(route('admin.generated-exports.show', $export));

        Queue::assertPushed(GenerateDailyStockReportExport::class, fn ($job) => $job->generatedExportId === $export->id);
        (new GenerateDailyStockReportExport($export->id))->handle(app(DailyStockReportQueryService::class));

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertCount(1, Storage::disk('local')->allFiles('exports/' . $admin->id . '/' . $export->id));
    }

    private function user(string $role, ?int $branchId, string $suffix = ''): User { $roleModel=Role::query()->firstOrCreate(['name'=>$role]); return User::query()->create(['name'=>ucfirst($role).' '.$suffix,'username'=>$role.'-'.$suffix.uniqid(),'email'=>$role.'-'.$suffix.uniqid().'@test.local','password'=>'secret123','role_id'=>$roleModel->id,'branch_id'=>$branchId]); }
    private function usageLog(Branch $branch, PaymentMethod $payment, string $status, string $suffix, int $number): void { $cashier=$this->user('kasir',$branch->id,'usage-'.$suffix); $transaction=Transaction::query()->create(['transaction_code'=>'TRX-USAGE-'.$suffix,'branch_id'=>$branch->id,'user_id'=>$cashier->id,'payment_method_id'=>$payment->id,'total_amount'=>1,'paid_amount'=>1,'change_amount'=>0,'status'=>$status]); $ingredient=Ingredient::query()->create(['name'=>'Bahan '.$suffix,'stock'=>10,'minimum_stock'=>1,'base_unit'=>'pcs','display_unit'=>'pcs']); StockLog::query()->create(['branch_id'=>$branch->id,'ingredient_id'=>$ingredient->id,'reference_id'=>$transaction->id,'type'=>'daily_usage','quantity'=>-1,'note'=>'usage '.$number]); }
}
