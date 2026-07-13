<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateExpenseExport;
use App\Jobs\GenerateSalesReportExport;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\GeneratedExport;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAndSalesExportResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_small_owner_expense_html_export_keeps_the_selected_branch_scope(): void
    {
        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $otherBranch = Branch::query()->create(['name' => 'Kebab SK Pati', 'code' => 'pti', 'is_active' => true]);

        $entry = $this->expense($owner, $branch, 'Operasional Utama');
        $this->expense($owner, $otherBranch, 'Operasional Cabang Lain');

        $this->assertSame('expense', $entry->type);
        $this->assertSame($branch->id, $entry->branch_id);
        $this->assertSame(now()->toDateString(), $entry->entry_date->toDateString());

        $this->assertSame(1, CashflowEntry::query()
            ->where('type', 'expense')
            ->where('branch_id', $branch->id)
            ->count());

        $this->assertSame(1, CashflowEntry::query()
            ->where('type', 'expense')
            ->where('branch_id', $branch->id)
            ->whereBetween('entry_date', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->count());

        $response = $this->withSession(['owner_active_branch_id' => $branch->id])
            ->actingAs($owner)
            ->get(route('owner.reports.cashflow.export', [
            'format' => 'html',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Operasional Utama');
        $response->assertDontSee('Operasional Cabang Lain');
        $response->assertSee('Rincian Pengeluaran');
    }

    public function test_large_owner_expense_excel_export_is_queued_with_an_explicit_branch_snapshot(): void
    {
        Queue::fake();
        Storage::fake('local');
        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();

        foreach (range(1, 251) as $number) {
            $this->expense($owner, $branch, 'Operasional ' . $number);
        }

        $this->actingAs($owner)
            ->get(route('owner.reports.cashflow.export', [
                'format' => 'excel',
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'branch_id' => $branch->id,
                'search' => 'Operasional',
            ]))
            ->assertRedirect();

        $export = GeneratedExport::query()->sole();
        $this->assertSame('expense_report', $export->type);
        $this->assertSame($branch->id, $export->branch_id);
        $this->assertSame(now()->toDateString(), $export->filters['date_from']);
        $this->assertSame('Operasional', $export->filters['search']);
        Queue::assertPushed(GenerateExpenseExport::class, fn ($job) => $job->generatedExportId === $export->id);

        (new GenerateExpenseExport($export->id))->handle(app(\App\Services\Exports\ExpenseExportQuery::class));

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);

        $otherOwner = $this->owner('other');
        $this->actingAs($otherOwner)
            ->get(route('owner.generated-exports.download', $export))
            ->assertForbidden();
    }

    public function test_large_owner_sales_excel_export_is_queued_and_keeps_success_summary_scope(): void
    {
        Queue::fake();
        Storage::fake('local');
        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $otherBranch = Branch::query()->create(['name' => 'Kebab SK Jepara', 'code' => 'jpr', 'is_active' => true]);
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Tunai']);

        foreach (range(1, 251) as $number) {
            $this->transaction($owner, $branch, $paymentMethod, 'SUCCESS', $number);
        }
        $this->transaction($owner, $branch, $paymentMethod, 'VOID', 999);
        $this->transaction($owner, $otherBranch, $paymentMethod, 'SUCCESS', 1000);

        $this->actingAs($owner)
            ->get(route('owner.reports.sales.export', [
                'format' => 'excel',
                'type' => 'daily',
                'date' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect();

        $export = GeneratedExport::query()->sole();
        $this->assertSame('sales_report', $export->type);
        $this->assertSame($branch->id, $export->branch_id);
        $this->assertSame(now()->toDateString(), $export->filters['date_from']);
        $this->assertSame('daily', $export->filters['type']);
        Queue::assertPushed(GenerateSalesReportExport::class, fn ($job) => $job->generatedExportId === $export->id);

        $summary = app(\App\Services\Exports\SalesReportExportQuery::class)
            ->summary(now()->startOfDay(), now()->endOfDay(), $branch->id);
        $this->assertSame(251, $summary['totalTransactions']);
        $this->assertSame(251000.0, $summary['totalRevenue']);

        (new GenerateSalesReportExport($export->id))->handle(app(\App\Services\Exports\SalesReportExportQuery::class));

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);

        $otherOwner = $this->owner('sales-other');
        $this->actingAs($otherOwner)
            ->get(route('owner.generated-exports.download', $export))
            ->assertForbidden();
    }

    public function test_large_admin_expense_excel_export_uses_the_admin_branch_snapshot(): void
    {
        Queue::fake();

        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $admin = $this->admin($branch);
        foreach (range(1, 251) as $number) {
            $this->expense($admin, $branch, 'Admin Operasional ' . $number);
        }

        $this->actingAs($admin)
            ->get(route('admin.reports.cashflow.export', [
                'format' => 'excel',
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertRedirect();

        $export = GeneratedExport::query()->sole();
        $this->assertSame('expense_report', $export->type);
        $this->assertSame($branch->id, $export->branch_id);
        Queue::assertPushed(GenerateExpenseExport::class, fn ($job) => $job->generatedExportId === $export->id);
    }

    public function test_large_direct_expense_html_export_is_rejected_before_rendering(): void
    {
        Queue::fake();

        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        foreach (range(1, 251) as $number) {
            $this->expense($owner, $branch, 'HTML Operasional ' . $number);
        }

        $this->actingAs($owner)
            ->get(route('owner.reports.cashflow.export', [
                'format' => 'html',
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('export');

        $this->assertDatabaseCount('generated_exports', 0);
        Queue::assertNothingPushed();
    }

    public function test_large_direct_sales_pdf_export_is_rejected_before_rendering(): void
    {
        Queue::fake();

        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $paymentMethod = PaymentMethod::query()->create(['name' => 'Tunai']);
        foreach (range(1, 251) as $number) {
            $this->transaction($owner, $branch, $paymentMethod, 'SUCCESS', $number);
        }

        $this->actingAs($owner)
            ->get(route('owner.reports.sales.export', [
                'format' => 'pdf',
                'type' => 'daily',
                'date' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('export');

        $this->assertDatabaseCount('generated_exports', 0);
        Queue::assertNothingPushed();
    }

    public function test_failed_expense_export_can_be_retried_without_leaving_an_orphan_file(): void
    {
        Queue::fake();
        Storage::fake('local');

        $owner = $this->owner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $export = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'branch_id' => $branch->id,
            'type' => 'expense_report',
            'format' => 'excel',
            'filters' => [
                'type' => 'daily',
                'date_from' => 'not-a-date',
                'date_to' => 'not-a-date',
                'search' => '',
            ],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'Pengeluaran_Test.xlsx',
            'expires_at' => now()->addDays(7),
        ]);

        try {
            (new GenerateExpenseExport($export->id))->handle(app(\App\Services\Exports\ExpenseExportQuery::class));
            $this->fail('Job ekspor dengan periode tidak valid seharusnya gagal.');
        } catch (\Throwable) {
            // Job sengaja meneruskan exception agar mekanisme retry queue tetap bekerja.
        }

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_FAILED, $export->status);
        $this->assertNull($export->file_path);
        Storage::disk('local')->assertMissing('exports/' . $owner->id . '/' . $export->id . '/Pengeluaran_Test.xlsx');

        $export->forceFill([
            'filters' => [
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'search' => '',
            ],
        ])->save();

        $this->actingAs($owner)
            ->post(route('owner.generated-exports.retry', $export))
            ->assertRedirect(route('owner.generated-exports.show', $export));

        Queue::assertPushed(GenerateExpenseExport::class, fn ($job) => $job->generatedExportId === $export->id);
        (new GenerateExpenseExport($export->id))->handle(app(\App\Services\Exports\ExpenseExportQuery::class));

        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertCount(1, Storage::disk('local')->allFiles('exports/' . $owner->id . '/' . $export->id));
    }

    private function owner(string $suffix = ''): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'owner']);

        $suffix = $suffix === '' ? '' : '-' . $suffix;

        return User::query()->create([
            'name' => 'Owner Export' . $suffix,
            'username' => 'owner-export' . $suffix,
            'email' => 'owner-export' . $suffix . '@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
        ]);
    }

    private function admin(Branch $branch): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin']);

        return User::query()->create([
            'name' => 'Admin Export',
            'username' => 'admin-export',
            'email' => 'admin-export@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
    }

    private function expense(User $owner, Branch $branch, string $source): CashflowEntry
    {
        return CashflowEntry::query()->create([
            'entry_date' => now()->toDateString(),
            'branch_id' => $branch->id,
            'type' => 'expense',
            'amount' => 1000,
            'source' => $source,
            'note' => 'Catatan ' . $source,
            'created_by' => $owner->id,
        ]);
    }

    private function transaction(User $owner, Branch $branch, PaymentMethod $paymentMethod, string $status, int $number): void
    {
        Transaction::query()->create([
            'transaction_code' => 'TRX-EXPORT-' . $number,
            'branch_id' => $branch->id,
            'user_id' => $owner->id,
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'change_amount' => 0,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
