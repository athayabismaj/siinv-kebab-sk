<?php

namespace Tests\Feature\Console;

use App\Models\Branch;
use App\Models\DailyStockSession;
use App\Models\Role;
use App\Models\User;
use App\Services\DailyStockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCloseStockSessionsGraceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_auto_close_waits_until_0300_and_only_closes_older_sessions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 02:59:00', 'Asia/Jakarta'));
        [$cashier, $admin, $branch] = $this->createUsers();
        $service = app(DailyStockService::class);
        $previousSession = $service->openSession(
            Carbon::parse('2026-08-08', 'Asia/Jakarta'),
            $cashier->id,
            $admin->id,
            null,
            $branch->id,
        );
        $todaySession = $service->openSession(
            Carbon::parse('2026-08-09', 'Asia/Jakarta'),
            $cashier->id,
            $admin->id,
            null,
            $branch->id,
        );

        $this->artisan('ops:auto-close-stock-sessions')
            ->expectsOutputToContain('Grace period closing masih aktif sampai 03:00.')
            ->assertSuccessful();

        $this->assertSessionStatus($previousSession, 'open');
        $this->assertSessionStatus($todaySession, 'open');

        Carbon::setTestNow(Carbon::parse('2026-08-09 03:00:00', 'Asia/Jakarta'));

        $this->artisan('ops:auto-close-stock-sessions')
            ->assertSuccessful();

        $this->assertSessionStatus($previousSession, 'closed');
        $this->assertSessionStatus($todaySession, 'open');
    }

    /**
     * @return array{User, User, Branch}
     */
    private function createUsers(): array
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Kebab SK', 'is_active' => true],
        );
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
        ]);
        $cashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
        ]);

        return [$cashier, $admin, $branch];
    }

    private function assertSessionStatus(DailyStockSession $session, string $status): void
    {
        $this->assertDatabaseHas('daily_stock_sessions', [
            'id' => $session->id,
            'status' => $status,
        ]);
    }
}
