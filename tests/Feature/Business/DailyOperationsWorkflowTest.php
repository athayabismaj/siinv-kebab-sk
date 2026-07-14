<?php

namespace Tests\Feature\Business;

use App\Actions\DailyStock\CloseDailyStockSessionAction;
use App\Actions\DailyStock\OpenDailyStockSessionAction;
use App\Actions\DailyStock\TransferToDailyStockAction;
use App\Actions\Sales\CheckoutTransactionAction;
use App\Actions\Sales\VoidTransactionAction;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use App\Models\Branch;
use App\Models\DailySalesSummary;
use App\Models\DailyStockItem;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_operations_preserve_stock_and_summary_after_void_restock_and_close(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 10:00:00', 'Asia/Jakarta'));

        try {
            [$branch, $admin, $cashier, $ingredient, $variant, $payment] = $this->createOperationalDataset();

            $session = app(OpenDailyStockSessionAction::class)->execute(
                now('Asia/Jakarta')->toDateString(),
                $cashier->id,
                $admin->id,
                branchId: $branch->id,
            );

            $transfer = app(TransferToDailyStockAction::class)->executeBatch(
                $session->id,
                [$ingredient->id => ['qty' => 10, 'note' => 'Stok operasional']],
                $admin->id,
                $branch->id,
            );

            $this->assertSame(1, $transfer['processed']);
            $this->assertSame(90.0, (float) $ingredient->fresh()->stock);

            $checkout = app(CheckoutTransactionAction::class)->execute([
                'payment_method_id' => $payment->id,
                'paid_amount' => 20000,
                'items' => [['variant_id' => $variant->id, 'qty' => 2]],
            ], $cashier->id);

            $this->assertTrue($checkout['ok']);
            $transaction = Transaction::query()->findOrFail($checkout['result']['transaction_id']);
            $dailyItem = DailyStockItem::query()->where('daily_stock_session_id', $session->id)->firstOrFail();

            $this->assertSame('SUCCESS', $transaction->status);
            $this->assertSame(8.0, (float) $dailyItem->remaining_qty);
            $this->assertSame(2.0, (float) $dailyItem->used_qty);
            $this->assertDatabaseHas('daily_sales_summaries', [
                'branch_id' => $branch->id,
                'sale_date' => '2026-07-14',
                'total_transactions' => 1,
                'total_revenue' => 20000,
                'total_items_sold' => 2,
            ]);

            app(VoidTransactionAction::class)->execute(new VoidTransactionRequestDto(
                $transaction->id,
                $session->id,
                $cashier->fresh('role'),
                'daily-operations-void-'.$transaction->id,
                VoidInventoryActionEnum::RESTOCK,
            ));

            $dailyItem->refresh();
            $this->assertSame('VOID', $transaction->fresh()->status);
            $this->assertSame(10.0, (float) $dailyItem->remaining_qty);
            $this->assertSame(0.0, (float) $dailyItem->used_qty);
            $this->assertDatabaseHas('daily_sales_summaries', [
                'branch_id' => $branch->id,
                'sale_date' => '2026-07-14',
                'total_transactions' => 0,
                'total_revenue' => 0,
                'total_items_sold' => 0,
            ]);

            app(CloseDailyStockSessionAction::class)->execute(
                $session->id,
                [$ingredient->id => 10],
                $admin->id,
                branchId: $branch->id,
            );

            $this->assertSame('closed', $session->fresh()->status);
            $this->assertSame(100.0, (float) $ingredient->fresh()->stock);
            $this->assertDatabaseHas('stock_logs', [
                'branch_id' => $branch->id,
                'ingredient_id' => $ingredient->id,
                'type' => 'daily_return',
                'quantity' => 10,
                'reference_id' => $session->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_void_in_one_branch_does_not_change_another_branch_session_or_summary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 11:00:00', 'Asia/Jakarta'));

        try {
            [$branchA, $adminA, $cashierA, $ingredient, $variant, $payment] = $this->createOperationalDataset();
            $branchB = Branch::query()->create([
                'name' => 'Kebab SK Jepara',
                'code' => 'jpr',
                'is_active' => true,
            ]);
            $adminB = $this->createUser('admin', $branchB, 'Admin Jepara');
            $cashierB = $this->createUser('kasir', $branchB, 'Kasir Jepara');

            $sessionA = $this->openAndTransfer($branchA, $adminA, $cashierA, $ingredient);
            $sessionB = $this->openAndTransfer($branchB, $adminB, $cashierB, $ingredient);
            $transactionA = $this->checkout($cashierA, $variant, $payment);
            $transactionB = $this->checkout($cashierB, $variant, $payment);

            app(VoidTransactionAction::class)->execute(new VoidTransactionRequestDto(
                $transactionA->id,
                $sessionA->id,
                $cashierA->fresh('role'),
                'cross-branch-void-'.$transactionA->id,
                VoidInventoryActionEnum::RESTOCK,
            ));

            $itemA = DailyStockItem::query()->where('daily_stock_session_id', $sessionA->id)->firstOrFail();
            $itemB = DailyStockItem::query()->where('daily_stock_session_id', $sessionB->id)->firstOrFail();

            $this->assertSame('VOID', $transactionA->fresh()->status);
            $this->assertSame('SUCCESS', $transactionB->fresh()->status);
            $this->assertSame(10.0, (float) $itemA->fresh()->remaining_qty);
            $this->assertSame(0.0, (float) $itemA->fresh()->used_qty);
            $this->assertSame(9.0, (float) $itemB->fresh()->remaining_qty);
            $this->assertSame(1.0, (float) $itemB->fresh()->used_qty);

            $this->assertSame(0, (int) DailySalesSummary::query()
                ->where('branch_id', $branchA->id)
                ->whereDate('sale_date', '2026-07-14')
                ->value('total_transactions'));
            $this->assertSame(1, (int) DailySalesSummary::query()
                ->where('branch_id', $branchB->id)
                ->whereDate('sale_date', '2026-07-14')
                ->value('total_transactions'));
            $this->assertSame(10000.0, (float) DailySalesSummary::query()
                ->where('branch_id', $branchB->id)
                ->whereDate('sale_date', '2026-07-14')
                ->value('total_revenue'));
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{Branch, User, User, Ingredient, MenuVariant, PaymentMethod}
     */
    private function createOperationalDataset(): array
    {
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $branch->update(['code' => 'umk']);
        $admin = $this->createUser('admin', $branch, 'Admin UMK');
        $cashier = $this->createUser('kasir', $branch, 'Kasir UMK');
        $category = IngredientCategory::query()->create(['name' => 'Bahan Operasional']);
        $ingredient = Ingredient::query()->create([
            'category_id' => $category->id,
            'name' => 'Tortilla Operasional',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
            'selling_price' => 1000,
        ]);
        $menu = Menu::query()->create([
            'name' => 'Kebab Operasional',
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Reguler',
            'price' => 10000,
            'is_available' => true,
            'sort_order' => 0,
        ]);
        DB::table('menu_variant_ingredients')->insert([
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        return [$branch, $admin, $cashier, $ingredient, $variant, $payment];
    }

    private function createUser(string $roleName, Branch $branch, string $name): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'name' => $name,
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
    }

    private function openAndTransfer(Branch $branch, User $admin, User $cashier, Ingredient $ingredient)
    {
        $session = app(OpenDailyStockSessionAction::class)->execute(
            now('Asia/Jakarta')->toDateString(),
            $cashier->id,
            $admin->id,
            branchId: $branch->id,
        );

        app(TransferToDailyStockAction::class)->executeBatch(
            $session->id,
            [$ingredient->id => ['qty' => 10, 'note' => null]],
            $admin->id,
            $branch->id,
        );

        return $session;
    }

    private function checkout(User $cashier, MenuVariant $variant, PaymentMethod $payment): Transaction
    {
        $checkout = app(CheckoutTransactionAction::class)->execute([
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ], $cashier->id);

        $this->assertTrue($checkout['ok']);

        return Transaction::query()->findOrFail($checkout['result']['transaction_id']);
    }
}
