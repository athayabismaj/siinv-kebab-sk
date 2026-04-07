<?php

namespace Tests\Feature\Owner;

use App\Models\Menu;
use App\Models\MenuCategory;
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

        $trxToday = $this->createTransaction($owner, $menu, $paymentMethod, $today->copy()->addHours(9), 12000, 15000);
        $trxThisWeek = $this->createTransaction($owner, $menu, $paymentMethod, $weekStart->copy()->addDays(1)->addHours(10), 13000, 15000);
        $trxThisMonth = $this->createTransaction($owner, $menu, $paymentMethod, $monthStart->copy()->addDays(5)->addHours(11), 14000, 15000);
        $trxOld = $this->createTransaction($owner, $menu, $paymentMethod, $monthStart->copy()->subMonth()->addDays(3), 16000, 20000);

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
        $daily->assertSee('Analisis Penjualan');

        $weekly = $this->actingAs($owner)->get(route('owner.reports.sales', [
            'type' => 'weekly',
            'week_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]));
        $weekly->assertOk();
        $weekly->assertSee('Analisis Penjualan');

        $monthly = $this->actingAs($owner)->get(route('owner.reports.sales', [
            'type' => 'monthly',
            'month' => now()->format('Y-m'),
        ]));
        $monthly->assertOk();
        $monthly->assertSee('Analisis Penjualan');
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
        float $paidAmount
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
