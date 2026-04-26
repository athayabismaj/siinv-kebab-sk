<?php

namespace Tests\Feature\Admin;

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
use Tests\TestCase;

class DailyStockIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrity_audit_reports_mismatch_between_session_item_and_transaction_usage_log(): void
    {
        [$cashier, $ingredient] = $this->dataset();

        $session = DailyStockSession::create([
            'session_date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        DailyStockItem::create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 200,
            'remaining_qty' => 20,
            'used_qty' => 180, // mismatch intentional
            'returned_qty' => 0,
        ]);

        $payment = PaymentMethod::create(['name' => 'Tunai']);
        $trx = Transaction::create([
            'transaction_code' => 'TRX-AUDIT-0001',
            'user_id' => $cashier->id,
            'total_amount' => 10000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        StockLog::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -200,
            'reference_id' => $trx->id,
            'note' => 'Drift test',
        ]);

        $this->artisan('ops:daily-stock-integrity-audit', [
            '--date' => now()->toDateString(),
        ])->expectsOutputToContain('findings=1')
            ->assertExitCode(1);
    }

    /**
     * @return array{0: User, 1: Ingredient}
     */
    private function dataset(): array
    {
        $cashierRole = Role::create(['name' => 'kasir']);
        $cashier = User::create([
            'name' => 'Kasir Audit',
            'username' => 'kasir_audit',
            'email' => 'kasir-audit@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
        ]);

        $category = IngredientCategory::create(['name' => 'Bahan Audit']);
        $ingredient = Ingredient::create([
            'category_id' => $category->id,
            'name' => 'Saus Audit',
            'display_unit' => 'ml',
            'base_unit' => 'ml',
            'pack_size' => 1,
            'stock' => 1000,
            'minimum_stock' => 5,
        ]);

        return [$cashier, $ingredient];
    }
}

