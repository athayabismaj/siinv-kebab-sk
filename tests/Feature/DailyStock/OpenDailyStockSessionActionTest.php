<?php

namespace Tests\Feature\DailyStock;

use App\Actions\DailyStock\OpenDailyStockSessionAction;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class OpenDailyStockSessionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_new_session_creates_only_the_session_row(): void
    {
        [$branch, $admin, $cashier] = $this->context();

        $session = app(OpenDailyStockSessionAction::class)->execute(
            '2026-07-16',
            $cashier->id,
            $admin->id,
            'Persiapan operasional',
            $branch->id,
        );

        $this->assertSame($branch->id, $session->branch_id);
        $this->assertSame($cashier->id, $session->cashier_id);
        $this->assertSame('open', $session->status);
        $this->assertDatabaseCount('daily_stock_sessions', 1);
        $this->assertSame(0, DailyStockItem::query()->count());
        $this->assertSame(0, StockLog::query()->count());
    }

    public function test_opening_an_existing_open_session_returns_the_same_session(): void
    {
        [$branch, $admin, $cashier] = $this->context();
        $action = app(OpenDailyStockSessionAction::class);

        $first = $action->execute('2026-07-16', $cashier->id, $admin->id, branchId: $branch->id);
        $second = $action->execute(
            '2026-07-16',
            $cashier->id,
            $admin->id,
            'Catatan terbaru',
            $branch->id,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Catatan terbaru', $second->notes);
        $this->assertDatabaseCount('daily_stock_sessions', 1);
    }

    public function test_opening_an_existing_closed_session_keeps_the_existing_domain_error(): void
    {
        [$branch, $admin, $cashier] = $this->context();
        $action = app(OpenDailyStockSessionAction::class);
        $session = $action->execute('2026-07-16', $cashier->id, $admin->id, branchId: $branch->id);
        $session->update(['status' => 'closed']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sesi stok harian untuk tanggal ini sudah ditutup.');

        $action->execute('2026-07-16', $cashier->id, $admin->id, branchId: $branch->id);
    }

    public function test_different_cashiers_and_dates_can_have_independent_sessions(): void
    {
        [$branch, $admin, $firstCashier] = $this->context();
        $cashierRole = Role::query()->where('name', 'kasir')->firstOrFail();
        $secondBranch = Branch::query()->create([
            'name' => 'Cabang Concurrency Dua',
            'code' => 'CC2',
            'is_active' => true,
        ]);
        $secondCashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'branch_id' => $secondBranch->id,
        ]);
        $action = app(OpenDailyStockSessionAction::class);

        $first = $action->execute('2026-07-16', $firstCashier->id, $admin->id, branchId: $branch->id);
        $second = $action->execute('2026-07-16', $secondCashier->id, $admin->id, branchId: $secondBranch->id);
        $nextDay = $action->execute('2026-07-17', $firstCashier->id, $admin->id, branchId: $branch->id);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->id, $nextDay->id);
        $this->assertDatabaseCount('daily_stock_sessions', 3);
    }

    public function test_only_the_target_postgresql_unique_violation_is_recognized(): void
    {
        $action = new class extends OpenDailyStockSessionAction
        {
            public function recognizes(QueryException $exception): bool
            {
                return $this->isDailySessionUniqueViolation($exception);
            }
        };

        $this->assertTrue($action->recognizes($this->uniqueViolation(
            'daily_stock_session_date_cashier_unique',
            'insert into "daily_stock_sessions" ("session_date", "cashier_id") values (?, ?)',
        )));
        $this->assertFalse($action->recognizes($this->uniqueViolation(
            'another_unique_constraint',
            'insert into "daily_stock_sessions" ("session_date", "cashier_id") values (?, ?)',
        )));
        $this->assertFalse($action->recognizes($this->uniqueViolation(
            'daily_stock_session_date_cashier_unique',
            'insert into "users" ("email") values (?)',
        )));
        $this->assertFalse($action->recognizes($this->uniqueViolation(
            'daily_stock_session_date_cashier_unique',
            'insert into "daily_stock_sessions" ("session_date", "cashier_id") values (?, ?)',
            '40001',
        )));
    }

    public function test_opening_transaction_rolls_back_when_a_session_side_effect_fails(): void
    {
        [$branch, $admin, $cashier] = $this->context();
        $eventName = 'eloquent.created: '.DailyStockSession::class;
        Event::listen($eventName, static function (): void {
            throw new RuntimeException('Simulated session side-effect failure.');
        });

        try {
            app(OpenDailyStockSessionAction::class)->execute(
                '2026-07-16',
                $cashier->id,
                $admin->id,
                branchId: $branch->id,
            );

            $this->fail('The simulated side-effect failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated session side-effect failure.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseCount('daily_stock_sessions', 0);
        $this->assertSame(0, DailyStockItem::query()->count());
        $this->assertSame(0, StockLog::query()->count());
    }

    /** @return array{Branch, User, User} */
    private function context(): array
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang Concurrency',
            'code' => 'CCY',
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

        return [$branch, $admin, $cashier];
    }

    private function uniqueViolation(string $constraint, string $sql, string $sqlState = '23505'): QueryException
    {
        $previous = new PDOException(
            'ERROR: duplicate key value violates unique constraint "'.$constraint.'"'
        );
        $previous->errorInfo = [$sqlState, 7, 'duplicate key'];

        return new QueryException('pgsql', $sql, [], $previous, ['driver' => 'pgsql']);
    }
}
