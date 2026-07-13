<?php

namespace App\Services\System;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat
{
    private const CACHE_KEY = 'system:scheduler-heartbeat';

    public function __construct(private readonly SchedulerLockConfiguration $lockConfiguration)
    {
    }

    public function beat(): void
    {
        Cache::store($this->lockConfiguration->store())->put(
            self::CACHE_KEY,
            now()->toIso8601String(),
            now()->addSeconds(max(1, (int) config('health.scheduler_heartbeat_ttl_seconds', 600))),
        );
    }

    public function latest(): ?CarbonInterface
    {
        $value = Cache::store($this->lockConfiguration->store())->get(self::CACHE_KEY);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
