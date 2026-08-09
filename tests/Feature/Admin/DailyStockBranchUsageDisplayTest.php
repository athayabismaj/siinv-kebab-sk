<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DailyStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyStockBranchUsageDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_used_quantity_and_value_are_visible_for_each_admin_branch(): void
    {
        [$admin, $pekeng, $umk, $pekengCashier, $umkCashier, $ingredient] = $this->branchDataset();
        $pekengSession = $this->closedSession($pekeng, $pekengCashier, $admin, $ingredient, 2);
        $umkSession = $this->closedSession($umk, $umkCashier, $admin, $ingredient, 3);

        $this->actingAs($admin)->get(route('admin.daily-stocks.index', [
            'date' => now()->toDateString(),
            'cashier_id' => $pekengCashier->id,
        ]))
            ->assertOk()
            ->assertViewHas('session', fn ($session) => $session?->id === $pekengSession->id)
            ->assertSee('20.000', false);

        $admin->update(['branch_id' => $umk->id]);

        $this->actingAs($admin->fresh())->get(route('admin.daily-stocks.index', [
            'date' => now()->toDateString(),
            'cashier_id' => $umkCashier->id,
        ]))
            ->assertOk()
            ->assertViewHas('session', fn ($session) => $session?->id === $umkSession->id)
            ->assertViewHas('sessionItems', function ($items): bool {
                $item = collect($items->items())->first();

                return (float) $item->used_display === 3.0
                    && (float) $item->ingredient->cost_price === 10000.0;
            })
            ->assertSee('30.000', false);
    }

    public function test_reconcile_uses_explicit_session_and_branch_instead_of_cashier_date_guessing(): void
    {
        [$admin, $pekeng, $umk, $pekengCashier, $umkCashier, $ingredient] = $this->branchDataset();
        $pekengSession = $this->openSession($pekeng, $pekengCashier, $admin, $ingredient);
        $umkSession = $this->openSession($umk, $umkCashier, $admin, $ingredient);

        $pekengTransaction = $this->transaction($pekeng, $pekengCashier, $pekengSession, 'TRX-PKG-TEST');
        $umkTransaction = $this->transaction($umk, $umkCashier, $umkSession, 'TRX-UMK-TEST');
        $this->usageLog($pekeng, $ingredient, $pekengTransaction, 2);
        $this->usageLog($umk, $ingredient, $umkTransaction, 3);

        app(DailyStockService::class)->reconcileSessionUsage($umkSession->id);

        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $umkSession->id,
            'ingredient_id' => $ingredient->id,
            'used_qty' => 3,
            'remaining_qty' => 7,
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $pekengSession->id,
            'ingredient_id' => $ingredient->id,
            'used_qty' => 0,
            'remaining_qty' => 10,
        ]);
    }

    /**
     * @return array{User, Branch, Branch, User, User, Ingredient}
     */
    private function branchDataset(): array
    {
        $pekeng = Branch::query()->firstOrCreate(
            ['code' => 'PKG'],
            ['name' => 'Pekeng', 'is_active' => true],
        );
        $umk = Branch::query()->firstOrCreate(
            ['code' => 'UMK'],
            ['name' => 'UMK', 'is_active' => true],
        );
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => $pekeng->id,
        ]);
        $admin->assignedBranches()->attach([$pekeng->id, $umk->id]);
        $pekengCashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $pekeng->id,
        ]);
        $umkCashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $umk->id,
        ]);
        $ingredient = Ingredient::query()->create([
            'name' => 'Tortilla Cabang',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 10,
            'selling_price' => 15000,
            'cost_price' => 10000,
        ]);

        return [$admin, $pekeng, $umk, $pekengCashier, $umkCashier, $ingredient];
    }

    private function openSession(Branch $branch, User $cashier, User $admin, Ingredient $ingredient): DailyStockSession
    {
        $session = DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        return $session;
    }

    private function closedSession(
        Branch $branch,
        User $cashier,
        User $admin,
        Ingredient $ingredient,
        float $used,
    ): DailyStockSession {
        $session = $this->openSession($branch, $cashier, $admin, $ingredient);
        $session->update([
            'status' => 'closed',
            'closed_by' => $admin->id,
            'closed_at' => now(),
        ]);
        $session->items()->firstOrFail()->update([
            'remaining_qty' => 10 - $used,
            'used_qty' => $used,
        ]);

        return $session;
    }

    private function transaction(
        Branch $branch,
        User $cashier,
        DailyStockSession $session,
        string $code,
    ): Transaction {
        $payment = PaymentMethod::query()->firstOrCreate(['name' => 'Tunai']);

        return Transaction::query()->create([
            'transaction_code' => $code,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'total_amount' => 10000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
            'daily_stock_session_id' => $session->id,
        ]);
    }

    private function usageLog(Branch $branch, Ingredient $ingredient, Transaction $transaction, float $quantity): void
    {
        StockLog::query()->create([
            'branch_id' => $branch->id,
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -$quantity,
            'reference_id' => $transaction->id,
            'note' => "Pemakaian stok harian dari transaksi #{$transaction->id}",
        ]);
    }
}
