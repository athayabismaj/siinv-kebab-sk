<?php

namespace Tests\Feature\Exports;

use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectExportSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_transaction_export_html_is_accessible(): void
    {
        [$owner] = $this->seedOwnerTransaction();

        $response = $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => 'html',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Laporan Riwayat Transaksi', false);
    }

    public function test_owner_transaction_export_excel_returns_download(): void
    {
        [$owner] = $this->seedOwnerTransaction();

        $response = $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => 'excel',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_owner_transaction_export_pdf_returns_download(): void
    {
        [$owner] = $this->seedOwnerTransaction();

        $response = $this->actingAs($owner)->get(route('owner.transactions.export', [
            'format' => 'pdf',
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedOwnerTransaction(): array
    {
        $ownerRole = Role::create(['name' => 'owner']);
        $cashierRole = Role::create(['name' => 'kasir']);

        $owner = User::create([
            'name' => 'Owner Test',
            'username' => 'owner_test',
            'email' => 'owner-test@example.test',
            'password' => 'secret123',
            'role_id' => $ownerRole->id,
        ]);

        $cashier = User::create([
            'name' => 'Kasir Test',
            'username' => 'kasir_test',
            'email' => 'kasir-test@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
        ]);

        $payment = PaymentMethod::create(['name' => 'Tunai']);

        Transaction::create([
            'transaction_code' => 'TRX-SMOKE-0001',
            'user_id' => $cashier->id,
            'total_amount' => 25000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 25000,
            'change_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $cashier];
    }
}
