<?php

namespace App\Services\System;

use Carbon\CarbonInterface;

class SystemHealthReport
{
    /**
     * @param array<int, HealthCheckResult> $checks
     */
    public function __construct(
        public readonly string $status,
        public readonly array $checks,
        public readonly CarbonInterface $checkedAt,
    ) {
    }

    public function httpStatus(): int
    {
        return $this->status === 'unhealthy' ? 503 : 200;
    }

    /**
     * @return array<string, array<int, array<string, array<string, bool|int|string|null>|int|string>|string>
     */
    public function toDiagnosticsArray(): array
    {
        return [
            'status' => $this->status,
            'checked_at' => $this->checkedAt->toIso8601String(),
            'checks' => array_map(
                static fn (HealthCheckResult $check) => $check->toArray(),
                $this->checks,
            ),
        ];
    }
}
