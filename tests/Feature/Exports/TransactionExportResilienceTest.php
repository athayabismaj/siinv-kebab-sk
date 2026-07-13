<?php

namespace Tests\Feature\Exports;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\GeneratedExport;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateTransactionExport;
use Tests\TestCase;

class TransactionExportResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_transaction_export_keeps_the_selected_branch_scope(): void
    {
        $owner = $this->createOwner();
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        $this->createTransaction($firstBranch, $payment, 'TRX-UMK-20260714-001');
        $this->createTransaction($secondBranch, $payment, 'TRX-JPR-20260714-001');

        $response = $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => 'html',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_id' => $firstBranch->id,
        ]));

        $response->assertOk();
        $response->assertSee('TRX-UMK-20260714-001');
        $response->assertDontSee('TRX-JPR-20260714-001');
    }

    public function test_large_transaction_excel_export_is_queued_instead_of_generated_in_the_web_request(): void
    {
        Queue::fake();

        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        foreach (range(1, 101) as $number) {
            $this->createTransaction($branch, $payment, sprintf('TRX-UMK-20260714-%03d', $number));
        }

        $response = $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => 'excel',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'branch_id' => $branch->id,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $generatedExport = GeneratedExport::query()->sole();
        $this->assertSame(GeneratedExport::STATUS_PENDING, $generatedExport->status);
        $this->assertSame($branch->id, $generatedExport->branch_id);
        $this->assertSame('transaction_history', $generatedExport->type);
        Queue::assertPushed(GenerateTransactionExport::class, fn (GenerateTransactionExport $job) => $job->generatedExportId === $generatedExport->id);
    }

    public function test_large_transaction_pdf_export_is_rejected_before_rendering(): void
    {
        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        foreach (range(1, 101) as $number) {
            $this->createTransaction($branch, $payment, sprintf('TRX-UMK-20260714-%03d', $number));
        }

        $this->actingAs($owner)
            ->get(route('owner.transactions.export', [
                'format' => 'pdf',
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('export');
    }

    public function test_large_transaction_html_export_is_rejected_before_rendering(): void
    {
        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        foreach (range(1, 101) as $number) {
            $this->createTransaction($branch, $payment, sprintf('TRX-UMK-HTML-%03d', $number));
        }

        $this->actingAs($owner)
            ->get(route('owner.transactions.export', [
                'format' => 'html',
                'type' => 'daily',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('export');
    }

    public function test_queued_transaction_export_creates_a_private_file_for_its_requester(): void
    {
        Storage::fake('local');

        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        $this->createTransaction($branch, $payment, 'TRX-UMK-20260714-001');

        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'branch_id' => $branch->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'search' => '',
                'user_id' => 0,
                'payment_method_id' => 0,
            ],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'Riwayat_Transaksi_14Jul2026.xlsx',
            'expires_at' => now()->addDays(7),
        ]);

        (new GenerateTransactionExport($generatedExport->id))->handle(app(\App\Services\Owner\TransactionHistoryQueryService::class));

        $generatedExport->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $generatedExport->status);
        Storage::disk('local')->assertExists($generatedExport->file_path);

        $this->actingAs($owner)
            ->get(route('owner.generated-exports.download', $generatedExport))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_a_second_worker_cannot_process_an_export_already_claimed_by_another_worker(): void
    {
        Storage::fake('local');

        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);
        $this->createTransaction($branch, $payment, 'TRX-UMK-20260714-LOCK');

        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'branch_id' => $branch->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'search' => '',
                'user_id' => 0,
                'payment_method_id' => 0,
            ],
            'status' => GeneratedExport::STATUS_PROCESSING,
            'started_at' => now(),
            'original_filename' => 'Riwayat_Transaksi_Terkunci.xlsx',
            'expires_at' => now()->addDays(7),
        ]);

        (new GenerateTransactionExport($generatedExport->id))
            ->handle(app(\App\Services\Owner\TransactionHistoryQueryService::class));

        $generatedExport->refresh();

        $this->assertSame(GeneratedExport::STATUS_PROCESSING, $generatedExport->status);
        $this->assertNull($generatedExport->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles('exports/' . $owner->id . '/' . $generatedExport->id));
    }

    public function test_failed_export_can_only_be_retried_once_when_two_requests_arrive_together(): void
    {
        Queue::fake();

        $owner = $this->createOwner();
        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => $this->transactionFilters(),
            'status' => GeneratedExport::STATUS_FAILED,
            'original_filename' => 'Riwayat_Transaksi_Retry.xlsx',
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($owner)
            ->post(route('owner.generated-exports.retry', $generatedExport))
            ->assertRedirect(route('owner.generated-exports.show', $generatedExport));

        $this->actingAs($owner)
            ->post(route('owner.generated-exports.retry', $generatedExport))
            ->assertNotFound();

        Queue::assertPushed(GenerateTransactionExport::class, 1);
    }

    public function test_other_owner_cannot_view_or_download_a_generated_export(): void
    {
        $owner = $this->createOwner();
        $otherOwner = User::query()->create([
            'name' => 'Owner Lain',
            'username' => 'owner_lain',
            'email' => 'owner-lain@example.test',
            'password' => 'secret123',
            'role_id' => $owner->role_id,
        ]);

        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'Riwayat_Transaksi.xlsx',
        ]);

        $this->actingAs($otherOwner)
            ->get(route('owner.generated-exports.show', $generatedExport))
            ->assertForbidden();

        $this->actingAs($otherOwner)
            ->get(route('owner.generated-exports.download', $generatedExport))
            ->assertForbidden();
    }

    public function test_expired_generated_export_is_removed_by_the_cleanup_command(): void
    {
        Storage::fake('local');

        $owner = $this->createOwner();
        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()],
            'status' => GeneratedExport::STATUS_COMPLETED,
            'file_disk' => 'local',
            'file_path' => 'exports/' . $owner->id . '/1/Riwayat_Transaksi.xlsx',
            'original_filename' => 'Riwayat_Transaksi.xlsx',
            'expires_at' => now()->subMinute(),
        ]);
        Storage::disk('local')->put($generatedExport->file_path, 'example');

        $this->artisan('exports:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('generated_exports', ['id' => $generatedExport->id]);
        Storage::disk('local')->assertMissing($generatedExport->file_path);
    }

    public function test_expired_generated_export_cannot_be_downloaded_before_cleanup_runs(): void
    {
        Storage::fake('local');

        $owner = $this->createOwner();
        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => $this->transactionFilters(),
            'status' => GeneratedExport::STATUS_COMPLETED,
            'file_disk' => 'local',
            'file_path' => 'exports/' . $owner->id . '/expired/Riwayat_Transaksi.xlsx',
            'original_filename' => 'Riwayat_Transaksi.xlsx',
            'expires_at' => now()->subMinute(),
        ]);
        Storage::disk('local')->put($generatedExport->file_path, 'expired');

        $this->actingAs($owner)
            ->get(route('owner.generated-exports.download', $generatedExport))
            ->assertNotFound();
    }

    public function test_cleanup_does_not_remove_an_export_that_is_still_processing(): void
    {
        $owner = $this->createOwner();
        $generatedExport = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => $this->transactionFilters(),
            'status' => GeneratedExport::STATUS_PROCESSING,
            'started_at' => now()->subMinute(),
            'original_filename' => 'Riwayat_Transaksi_Proses.xlsx',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('exports:cleanup')->assertExitCode(0);

        $this->assertDatabaseHas('generated_exports', ['id' => $generatedExport->id]);
    }

    private function createOwner(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'owner']);

        return User::query()->create([
            'name' => 'Owner Export',
            'username' => 'owner_export',
            'email' => 'owner-export@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
        ]);
    }

    private function transactionFilters(): array
    {
        return [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'search' => '',
            'user_id' => 0,
            'payment_method_id' => 0,
        ];
    }

    private function createTransaction(Branch $branch, PaymentMethod $payment, string $code): void
    {
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::query()->firstOrCreate(
            ['username' => 'cashier-' . $branch->id],
            [
                'name' => 'Kasir ' . $branch->name,
                'email' => 'cashier-' . $branch->id . '@example.test',
                'password' => 'secret123',
                'role_id' => $cashierRole->id,
                'branch_id' => $branch->id,
            ]
        );

        Transaction::query()->create([
            'transaction_code' => $code,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'payment_method_id' => $payment->id,
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
    }
}
