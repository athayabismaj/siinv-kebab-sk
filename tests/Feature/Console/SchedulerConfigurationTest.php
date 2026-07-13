<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    private string|false $previousLockStore;

    private string|false $previousMultiServer;

    protected function setUp(): void
    {
        $this->previousLockStore = getenv('SCHEDULER_CACHE_STORE');
        $this->previousMultiServer = getenv('SCHEDULER_MULTI_SERVER');
        putenv('SCHEDULER_CACHE_STORE=database');
        putenv('SCHEDULER_MULTI_SERVER=true');
        $_ENV['SCHEDULER_CACHE_STORE'] = 'database';
        $_ENV['SCHEDULER_MULTI_SERVER'] = 'true';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment('SCHEDULER_CACHE_STORE', $this->previousLockStore);
        $this->restoreEnvironment('SCHEDULER_MULTI_SERVER', $this->previousMultiServer);

        parent::tearDown();
    }

    public function test_critical_scheduled_events_have_stable_names_and_guards_when_multi_server_locking_is_enabled(): void
    {
        $events = collect(app(Schedule::class)->events())->keyBy('description');

        $this->assertScheduledEvent($events, 'sales-summary-current', '*/15 * * * *', 10, true);
        $this->assertScheduledEvent($events, 'sales-summary-rebuild', '10 2 * * *', 30, true);
        $this->assertScheduledEvent($events, 'daily-stock-integrity-audit', '20 3 * * *', 20, true);
        $this->assertScheduledEvent($events, 'daily-stock-auto-close', '0 4 * * *', 30, true);
        $this->assertScheduledEvent($events, 'exports-cleanup', '35 3 * * *', 10, true);
        $this->assertScheduledEvent($events, 'backup-daily', '0 1 * * *', 180, true);
        $this->assertScheduledEvent($events, 'backup-weekly', '0 2 * * 1', 180, true);
        $this->assertScheduledEvent($events, 'backup-monthly', '0 3 1 * *', 180, true);
    }

    /**
     * @param \Illuminate\Support\Collection<string, Event> $events
     */
    private function assertScheduledEvent($events, string $name, string $expression, int $lockMinutes, bool $oneServer): void
    {
        $event = $events->get($name);

        $this->assertInstanceOf(Event::class, $event, "Scheduled event [{$name}] was not found.");
        $this->assertSame($expression, $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame($lockMinutes, $event->expiresAt);
        $this->assertSame($oneServer, $event->onOneServer);
    }

    private function restoreEnvironment(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            unset($_ENV[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}
