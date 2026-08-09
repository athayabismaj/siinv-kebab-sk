<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\User;
use App\Services\Api\CashierOperationalContextResolver;
use App\Services\DailyStockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyStockClosingGracePeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_cashier_can_read_and_close_previous_day_session_until_0259(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 02:59:00', 'Asia/Jakarta'));
        [$cashier, $session, $ingredient, $token] = $this->createPreviousDaySession();

        $this->withToken($token)
            ->getJson('/api/sessions/current-status')
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.is_previous_day_session', true)
            ->assertJsonPath('data.closing_grace_until', '03:00');

        $this->withToken($token)
            ->getJson('/api/daily-stock-items')
            ->assertOk()
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.session_date', '2026-08-08')
            ->assertJsonPath('data.is_previous_day_session', true)
            ->assertJsonPath('data.closing_grace_until', '03:00')
            ->assertJsonPath('data.items.0.remaining_qty', 6);

        $this->withToken($token)
            ->postJson('/api/daily-stock-sessions/close', [
                'remaining' => [$ingredient->id => 4],
                'notes' => 'Closing terlambat sebelum batas toleransi.',
            ])
            ->assertOk()
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.session_date', '2026-08-08')
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('daily_stock_sessions', [
            'id' => $session->id,
            'cashier_id' => $cashier->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'remaining_qty' => 4,
            'used_qty' => 2,
        ]);
    }

    public function test_previous_day_session_is_no_longer_available_to_cashier_at_0300(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 03:00:00', 'Asia/Jakarta'));
        [, $session, $ingredient, $token] = $this->createPreviousDaySession();

        $this->withToken($token)
            ->getJson('/api/sessions/current-status')
            ->assertNotFound()
            ->assertJsonPath('active', false);

        $this->withToken($token)
            ->getJson('/api/daily-stock-items')
            ->assertOk()
            ->assertJsonPath('data.session_id', null)
            ->assertJsonPath('data.items', []);

        $this->withToken($token)
            ->postJson('/api/daily-stock-sessions/close', [
                'remaining' => [$ingredient->id => 4],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('daily_stock_sessions', [
            'id' => $session->id,
            'status' => 'open',
        ]);
    }

    public function test_previous_day_session_requires_explicit_closing_grace_context(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 02:30:00', 'Asia/Jakarta'));
        [$cashier, $session] = $this->createPreviousDaySession();
        $resolver = app(CashierOperationalContextResolver::class);

        $salesContext = $resolver->resolve($cashier);
        $closingContext = $resolver->resolve($cashier, allowPreviousDayClosingGrace: true);

        $this->assertNull($salesContext->session);
        $this->assertSame('2026-08-09', $salesContext->sessionDate);
        $this->assertSame($session->id, $closingContext->session?->id);
        $this->assertSame('2026-08-08', $closingContext->sessionDate);
    }

    /**
     * @return array{User, DailyStockSession, Ingredient, string}
     */
    private function createPreviousDaySession(): array
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
        $ingredient = Ingredient::query()->create([
            'name' => 'Tortilla Closing Grace',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 10,
            'stock' => 100,
            'minimum_stock' => 10,
            'selling_price' => 1000,
            'cost_price' => 500,
        ]);
        $session = app(DailyStockService::class)->openSession(
            Carbon::parse('2026-08-08', 'Asia/Jakarta'),
            $cashier->id,
            $admin->id,
            null,
            $branch->id,
        );
        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 6,
            'remaining_qty' => 6,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        $plainToken = 'daily_stock_grace_'.bin2hex(random_bytes(8));
        ApiToken::query()->create([
            'user_id' => $cashier->id,
            'name' => 'daily-stock-closing-grace-test',
            'token_hash' => hash('sha256', $plainToken),
            'last_used_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return [$cashier, $session, $ingredient, $plainToken];
    }
}
