<?php

namespace Tests\Feature\DailyStock;

use App\Actions\DailyStock\CloseDailyStockSessionAction;
use App\Actions\DailyStock\OpenDailyStockSessionAction;
use App\Actions\DailyStock\TransferToDailyStockAction;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockOpeningAdjustment;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarryForwardDailyStockActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_outlet_remainder_becomes_the_next_session_opening_balance(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $ingredient->update(['stock' => 100]);

        $firstSession = app(OpenDailyStockSessionAction::class)->execute(
            now()->subDay()->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );
        app(TransferToDailyStockAction::class)->executeBatch(
            $firstSession->id,
            [$ingredient->id => ['qty' => 10, 'note' => null]],
            $admin->id,
            $branch->id,
        );
        app(CloseDailyStockSessionAction::class)->execute(
            $firstSession->id,
            [$ingredient->id => 5],
            $admin->id,
            branchId: $branch->id,
        );

        $secondSession = app(OpenDailyStockSessionAction::class)->execute(
            now()->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );
        $carriedItem = $secondSession->items()->firstOrFail();

        $this->assertSame(90.0, (float) $ingredient->fresh()->stock);
        $this->assertTrue((bool) $firstSession->fresh()->stock_retained_at_outlet);
        $this->assertSame($firstSession->id, $secondSession->carry_forward_source_session_id);
        $this->assertSame(5.0, (float) $carriedItem->carry_forward_qty);
        $this->assertSame(5.0, (float) $carriedItem->opening_qty);
        $this->assertSame(5.0, (float) $carriedItem->remaining_qty);
        $this->assertDatabaseMissing('stock_logs', [
            'type' => 'daily_return',
            'reference_id' => $firstSession->id,
        ]);
    }

    public function test_legacy_returned_session_is_not_carried_forward_again(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $legacySession = DailyStockSession::query()->create([
            'session_date' => now()->subDay()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'closed_by' => $admin->id,
            'status' => 'closed',
            'stock_retained_at_outlet' => false,
            'opened_at' => now()->subDay()->startOfDay(),
            'closed_at' => now()->subDay()->endOfDay(),
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $legacySession->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 5,
            'used_qty' => 5,
            'returned_qty' => 5,
        ]);

        $session = app(OpenDailyStockSessionAction::class)->execute(
            now()->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );

        $this->assertNull($session->carry_forward_source_session_id);
        $this->assertSame(0, $session->items()->count());
    }

    public function test_stale_closed_remainder_is_not_reused_when_a_newer_session_is_still_open(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $staleSession = DailyStockSession::query()->create([
            'session_date' => now()->subDays(2)->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'closed_by' => $admin->id,
            'status' => 'closed',
            'stock_retained_at_outlet' => true,
            'opened_at' => now()->subDays(2)->startOfDay(),
            'closed_at' => now()->subDays(2)->endOfDay(),
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $staleSession->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 5,
            'remaining_qty' => 5,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);
        DailyStockSession::query()->create([
            'session_date' => now()->subDay()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'status' => 'open',
            'stock_retained_at_outlet' => false,
            'opened_at' => now()->subDay()->startOfDay(),
        ]);

        $session = app(OpenDailyStockSessionAction::class)->execute(
            now()->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );

        $this->assertNull($session->carry_forward_source_session_id);
        $this->assertSame(0, $session->items()->count());
    }

    public function test_matching_physical_stock_is_carried_without_reducing_warehouse_stock(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 5);

        $result = app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 0, 'physical_qty' => 5, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $result['reconciled']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(95.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(5.0, (float) $item->carry_forward_qty);
        $this->assertSame(5.0, (float) $item->opening_qty);
        $this->assertSame(5.0, (float) $item->remaining_qty);
        $this->assertSame(0, DailyStockOpeningAdjustment::query()->count());
        $this->assertSame(0, StockLog::query()->where('type', 'transfer_daily')->count());
    }

    public function test_only_new_warehouse_pickup_is_deducted_after_carry_forward(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 5);

        $result = app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 5, 'physical_qty' => 5, 'note' => 'Tambah operasional']],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $result['reconciled']);
        $this->assertSame(1, $result['processed']);
        $this->assertSame(90.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(5.0, (float) $item->carry_forward_qty);
        $this->assertSame(5.0, (float) $item->transferred_qty);
        $this->assertSame(10.0, (float) $item->opening_qty);
        $this->assertSame(10.0, (float) $item->remaining_qty);
        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'transfer_daily',
            'quantity' => -5.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_missing_physical_stock_is_audited_without_reducing_warehouse_stock(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 5);

        app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 0, 'physical_qty' => 4, 'note' => 'Selisih hitung fisik']],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(95.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(-1.0, (float) $item->opening_adjustment_qty);
        $this->assertSame(4.0, (float) $item->opening_qty);
        $this->assertSame(4.0, (float) $item->remaining_qty);
        $this->assertDatabaseHas('daily_stock_opening_adjustments', [
            'daily_stock_session_id' => $session->id,
            'daily_stock_item_id' => $item->id,
            'ingredient_id' => $ingredient->id,
            'expected_qty' => 5.00,
            'actual_qty' => 4.00,
            'difference_qty' => -1.00,
            'created_by' => $admin->id,
        ]);
        $this->assertSame(0, StockLog::query()->where('type', 'transfer_daily')->count());
    }

    public function test_later_warehouse_pickup_does_not_repeat_unchanged_physical_verification(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 5);

        $firstResult = app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 0, 'physical_qty' => 5, 'note' => null]],
            $admin->id,
            $branch->id,
        );
        $secondResult = app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 2, 'physical_qty' => 5, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $firstResult['reconciled']);
        $this->assertSame(0, $secondResult['reconciled']);
        $this->assertSame(1, $secondResult['processed']);
        $this->assertSame(7.0, (float) $item->opening_qty);
        $this->assertSame(2.0, (float) $item->transferred_qty);
        $this->assertSame(93.0, (float) $ingredient->fresh()->stock);
    }

    public function test_admin_pcs_pickup_updates_session_balance_and_uses_a_clear_success_message(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $ingredient->update(['stock' => 10, 'pack_size' => 10]);
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 8);
        app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 0, 'physical_qty' => 8, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $response = $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'opening_quantity' => 10,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ]);

        $item = $session->items()->firstOrFail();
        $response->assertRedirect()->assertSessionHas('success', '1 bahan tambahan berhasil diambil dari gudang.');
        $this->assertSame(8.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(8.0, (float) $item->carry_forward_qty);
        $this->assertSame(2.0, (float) $item->transferred_qty);
        $this->assertSame(10.0, (float) $item->opening_qty);
        $this->assertSame(10.0, (float) $item->remaining_qty);

        $this->actingAs($admin)
            ->get(route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]))
            ->assertOk()
            ->assertSeeText('Saldo Sesi: 10 PCS')
            ->assertSeeText('Sudah tambah: 2 PCS');
    }

    public function test_transfer_form_labels_the_editable_previous_remainder(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 5);

        $this->actingAs($admin)
            ->get(route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]))
            ->assertOk()
            ->assertSee('Sisa Kemarin')
            ->assertSee('Stok Awal Hari Ini')
            ->assertSee('Tambahan Gudang')
            ->assertSee('Saldo Sesi')
            ->assertSee('Langkah (+/-)')
            ->assertSee('transfers['.$ingredient->id.'][opening_quantity]', false);
    }

    public function test_total_opening_input_keeps_carry_forward_without_reducing_warehouse(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 8);

        $result = app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 8, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $result['reconciled']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(0, $result['returned']);
        $this->assertSame(95.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(8.0, (float) $item->opening_qty);
        $this->assertSame(0.0, (float) $item->transferred_qty);
    }

    public function test_total_opening_input_only_deducts_amount_above_carry_forward_once(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 8);
        $action = app(TransferToDailyStockAction::class);

        $first = $action->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 10, 'note' => null]],
            $admin->id,
            $branch->id,
        );
        $second = $action->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 10, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $first['processed']);
        $this->assertSame(0, $second['processed']);
        $this->assertSame(93.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(2.0, (float) $item->transferred_qty);
        $this->assertSame(10.0, (float) $item->opening_qty);
        $this->assertSame(1, StockLog::query()->where('type', 'transfer_daily')->count());
    }

    public function test_total_opening_input_below_carry_forward_is_a_physical_adjustment(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 8);

        app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 6, 'note' => 'Selisih fisik']],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(95.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(-2.0, (float) $item->opening_adjustment_qty);
        $this->assertSame(0.0, (float) $item->transferred_qty);
        $this->assertSame(6.0, (float) $item->opening_qty);
        $this->assertDatabaseHas('daily_stock_opening_adjustments', [
            'daily_stock_item_id' => $item->id,
            'expected_qty' => 8.00,
            'actual_qty' => 6.00,
            'difference_qty' => -2.00,
        ]);
        $this->assertSame(0, StockLog::query()->count());
    }

    public function test_lowering_a_previously_added_total_returns_only_the_difference_to_warehouse(): void
    {
        [$branch, $admin, $cashier, $ingredient] = $this->context();
        $session = $this->openTodayFromPreviousRemainder($branch, $admin, $cashier, $ingredient, 8);
        $action = app(TransferToDailyStockAction::class);
        $action->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 10, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $result = $action->executeBatch(
            $session->id,
            [$ingredient->id => ['target_opening_qty' => 9, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        $item = $session->items()->firstOrFail();
        $this->assertSame(1, $result['returned']);
        $this->assertSame(94.0, (float) $ingredient->fresh()->stock);
        $this->assertSame(1.0, (float) $item->transferred_qty);
        $this->assertSame(9.0, (float) $item->opening_qty);
        $this->assertDatabaseHas('stock_logs', [
            'type' => 'daily_return',
            'quantity' => 1.00,
            'reference_id' => $session->id,
        ]);
    }

    private function openTodayFromPreviousRemainder(
        Branch $branch,
        User $admin,
        User $cashier,
        Ingredient $ingredient,
        float $remaining
    ): DailyStockSession {
        $previous = DailyStockSession::query()->create([
            'session_date' => now()->subDay()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'closed_by' => $admin->id,
            'status' => 'closed',
            'stock_retained_at_outlet' => true,
            'opened_at' => now()->subDay()->startOfDay(),
            'closed_at' => now()->subDay()->endOfDay(),
        ]);
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $previous->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => $remaining,
            'used_qty' => 10 - $remaining,
            'returned_qty' => 0,
        ]);

        return app(OpenDailyStockSessionAction::class)->execute(
            now()->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );
    }

    /** @return array{Branch, User, User, Ingredient} */
    private function context(): array
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang Carry Forward',
            'code' => 'carry-forward',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->create(['name' => 'admin']);
        $cashierRole = Role::query()->create(['name' => 'kasir']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
        ]);
        $cashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
        ]);
        $ingredient = Ingredient::query()->create([
            'name' => 'Tortilla Carry Forward',
            'stock' => 95,
            'minimum_stock' => 5,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
        ]);

        return [$branch, $admin, $cashier, $ingredient];
    }
}
