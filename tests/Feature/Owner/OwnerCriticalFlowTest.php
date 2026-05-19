<?php

namespace Tests\Feature\Owner;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerCriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_transaction_history_page(): void
    {
        [$owner, $menu, $paymentMethod] = $this->createBaseOwnerDataset();
        $this->createTransaction($owner, $menu, $paymentMethod, now(), 15000, 20000);

        $response = $this->actingAs($owner)->get(route('owner.transactions.index'));

        $response->assertOk();
        $response->assertSee('Riwayat Transaksi');
        $response->assertSee('TRX-');
    }

    public function test_owner_transaction_history_period_filters_work(): void
    {
        [$owner, $menu, $paymentMethod] = $this->createBaseOwnerDataset();

        $today = now()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $weekInRangeDate = $weekStart->copy()->addDay();
        if ($weekInRangeDate->greaterThan($today)) {
            $weekInRangeDate = $weekStart->copy();
        }

        $monthInRangeDate = $monthStart->copy()->addDay();
        if ($monthInRangeDate->greaterThan($today)) {
            $monthInRangeDate = $monthStart->copy();
        }

        $trxToday = $this->createTransaction($owner, $menu, $paymentMethod, $today->copy()->addHours(9), 12000, 15000);
        $trxThisWeek = $this->createTransaction($owner, $menu, $paymentMethod, $weekInRangeDate->copy()->addHours(10), 13000, 15000);
        $trxThisMonth = $this->createTransaction($owner, $menu, $paymentMethod, $monthInRangeDate->copy()->addHours(11), 14000, 15000);
        $trxOld = $this->createTransaction($owner, $menu, $paymentMethod, $monthStart->copy()->subMonth()->startOfMonth()->addDays(3)->addHours(8), 16000, 20000);

        $daily = $this->actingAs($owner)->get(route('owner.transactions.index', [
            'type' => 'daily',
            'date_from' => $today->toDateString(),
            'date_to' => $today->toDateString(),
        ]));
        $daily->assertOk();
        $daily->assertSee($trxToday->transaction_code);
        $daily->assertDontSee($trxOld->transaction_code);

        $weekly = $this->actingAs($owner)->get(route('owner.transactions.index', [
            'type' => 'weekly',
            'date_from' => $weekStart->toDateString(),
            'date_to' => $weekEnd->toDateString(),
        ]));
        $weekly->assertOk();
        $weekly->assertSee($trxToday->transaction_code);
        $weekly->assertSee($trxThisWeek->transaction_code);
        $weekly->assertDontSee($trxOld->transaction_code);

        $monthly = $this->actingAs($owner)->get(route('owner.transactions.index', [
            'type' => 'monthly',
            'date_from' => $monthStart->toDateString(),
            'date_to' => $monthEnd->toDateString(),
        ]));
        $monthly->assertOk();
        $monthly->assertSee($trxToday->transaction_code);
        $monthly->assertSee($trxThisMonth->transaction_code);
        $monthly->assertDontSee($trxOld->transaction_code);
    }

    public function test_owner_sales_report_supports_daily_weekly_and_monthly(): void
    {
        [$owner, $menu, $paymentMethod] = $this->createBaseOwnerDataset();
        $this->createTransaction($owner, $menu, $paymentMethod, now()->startOfDay()->addHours(10), 18000, 20000);

        $daily = $this->actingAs($owner)->get(route('owner.reports.sales', [
            'type' => 'daily',
            'date' => now()->toDateString(),
        ]));
        $daily->assertOk();
        $daily->assertSee('Laporan Penjualan');

        $weekly = $this->actingAs($owner)->get(route('owner.reports.sales', [
            'type' => 'weekly',
            'week_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]));
        $weekly->assertOk();
        $weekly->assertSee('Laporan Penjualan');

        $monthly = $this->actingAs($owner)->get(route('owner.reports.sales', [
            'type' => 'monthly',
            'month' => now()->format('Y-m'),
        ]));
        $monthly->assertOk();
        $monthly->assertSee('Laporan Penjualan');
    }

    public function test_owner_menu_analysis_groups_by_menu_item_and_excludes_addons(): void
    {
        [$owner, $menu, $paymentMethod] = $this->createBaseOwnerDataset();
        $menuVariant = MenuVariant::create([
            'menu_id' => $menu->id,
            'name' => 'Mini',
            'price' => 18000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $addonCategory = MenuCategory::create([
            'name' => 'Kategori Add On Test',
            'is_addon' => true,
        ]);

        $addonMenu = Menu::create([
            'category_id' => $addonCategory->id,
            'name' => 'Extra Cheese Test',
            'description' => null,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $addonVariant = MenuVariant::create([
            'menu_id' => $addonMenu->id,
            'name' => 'Extra Cheese',
            'price' => 3000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $soldAt = now()->startOfDay()->addHours(10);
        $this->createTransaction($owner, $menu, $paymentMethod, $soldAt, 18000, 20000, $menuVariant);
        $this->createTransaction($owner, $addonMenu, $paymentMethod, $soldAt->copy()->addMinutes(5), 3000, 5000, $addonVariant);

        $response = $this->actingAs($owner)->get(route('owner.analytics.menu', [
            'type' => 'daily',
            'date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Menu Test Mini');
        $response->assertDontSee('Extra Cheese Test');
    }

    public function test_owner_menu_analysis_separates_sales_by_variant_name(): void
    {
        [$owner, $menu, $paymentMethod] = $this->createBaseOwnerDataset();

        $mini = MenuVariant::create([
            'menu_id' => $menu->id,
            'name' => 'Mini',
            'price' => 12000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $jumbo = MenuVariant::create([
            'menu_id' => $menu->id,
            'name' => 'Jumbo',
            'price' => 20000,
            'is_available' => true,
            'sort_order' => 2,
        ]);

        $soldAt = now()->startOfDay()->addHours(10);
        $this->createTransaction($owner, $menu, $paymentMethod, $soldAt, 12000, 15000, $mini);
        $this->createTransaction($owner, $menu, $paymentMethod, $soldAt->copy()->addMinutes(5), 20000, 25000, $jumbo);

        $response = $this->actingAs($owner)->get(route('owner.analytics.menu', [
            'type' => 'daily',
            'date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Menu Test Mini');
        $response->assertSee('Menu Test Jumbo');
    }

    /**
     * @return array{0:User, 1:Menu, 2:PaymentMethod}
     */
    private function createBaseOwnerDataset(): array
    {
        $ownerRole = Role::create(['name' => 'owner']);

        $owner = User::create([
            'name' => 'Owner Test',
            'username' => 'owner_test',
            'email' => 'owner@test.local',
            'password' => 'secret123',
            'role_id' => $ownerRole->id,
        ]);

        $category = MenuCategory::create(['name' => 'Kategori Test']);
        $menu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Menu Test',
            'description' => null,
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $paymentMethod = PaymentMethod::create(['name' => 'Cash']);

        return [$owner, $menu, $paymentMethod];
    }

    private function createTransaction(
        User $user,
        Menu $menu,
        PaymentMethod $paymentMethod,
        Carbon $createdAt,
        float $totalAmount,
        float $paidAmount,
        ?MenuVariant $variant = null
    ): Transaction {
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-' . $createdAt->format('YmdHis') . '-' . random_int(100, 999),
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'change_amount' => max(0, $paidAmount - $totalAmount),
        ]);

        $transaction->timestamps = false;
        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_variant_id' => $variant?->id,
            'quantity' => 1,
            'price' => $totalAmount,
            'subtotal' => $totalAmount,
        ]);

        $detail->timestamps = false;
        $detail->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $transaction;
    }
}
