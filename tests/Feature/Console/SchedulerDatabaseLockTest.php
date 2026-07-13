<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchedulerDatabaseLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_cache_lock_allows_only_one_scheduler_instance_to_hold_a_critical_event_lock(): void
    {
        $store = Cache::store($this->lockStore());
        $first = $store->lock('scheduler-test:exports-cleanup', 60);
        $second = $store->lock('scheduler-test:exports-cleanup', 60);

        $this->assertTrue($first->get());
        $this->assertFalse($second->get());

        $first->release();

        $this->assertTrue($second->get());
        $second->release();
    }

    public function test_expired_database_lock_can_be_reclaimed_without_blocking_a_different_event(): void
    {
        $storeName = $this->lockStore();
        $store = Cache::store($storeName);
        $cleanup = $store->lock('scheduler-test:exports-cleanup', 60);
        $summary = $store->lock('scheduler-test:sales-summary-current', 60);

        $this->assertTrue($cleanup->get());
        $this->assertTrue($summary->get());

        DB::connection($this->lockConnection())
            ->table('cache_locks')
            ->where('key', config('cache.prefix') . 'scheduler-test:exports-cleanup')
            ->update(['expiration' => now()->subSecond()->timestamp]);

        $replacement = $store->lock('scheduler-test:exports-cleanup', 60);

        $this->assertTrue($replacement->get());

        $summary->release();
        $replacement->release();
    }

    private function lockStore(): string
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return 'database';
        }

        $connection = (array) config('database.connections.' . config('database.default'));
        config([
            'database.connections.scheduler_locks' => $connection,
            'cache.stores.scheduler_locks' => [
                'driver' => 'database',
                'connection' => 'scheduler_locks',
                'table' => 'cache',
                'lock_connection' => 'scheduler_locks',
                'lock_table' => 'cache_locks',
            ],
        ]);

        DB::purge('scheduler_locks');

        return 'scheduler_locks';
    }

    private function lockConnection(): ?string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'scheduler_locks' : null;
    }
}
