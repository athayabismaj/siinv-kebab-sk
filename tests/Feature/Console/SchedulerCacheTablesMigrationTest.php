<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchedulerCacheTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_cache_tables_migration_can_be_reverted_and_reapplied(): void
    {
        $migration = require database_path('migrations/2026_07_14_010000_create_cache_tables_for_scheduler_locks.php');

        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('cache'));
        $this->assertFalse(Schema::hasTable('cache_locks'));

        $migration->up();

        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));
    }
}
