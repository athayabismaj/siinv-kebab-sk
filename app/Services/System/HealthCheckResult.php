<?php

namespace App\Services\System;

use Carbon\CarbonInterface;

class HealthCheckResult
{
    /**
     * @param array<string, bool|int|string|null> $metadata
     */
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly string $message,
        public readonly array $metadata,
        public readonly CarbonInterface $checkedAt,
        public readonly int $durationMs,
    ) {
    }

    /**
     * @return array<string, array<string, bool|int|string|null>|int|string>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'checked_at' => $this->checkedAt->toIso8601String(),
            'duration_ms' => $this->durationMs,
        ];
    }
}
