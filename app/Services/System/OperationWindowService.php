<?php

namespace App\Services\System;

use Carbon\Carbon;

class OperationWindowService
{
    public function isOperationalNow(): bool
    {
        [$startHour, $endHour, $timezone] = $this->config();

        $now = now($timezone);
        $start = $now->copy()->startOfDay()->addHours($startHour);
        $end = $now->copy()->startOfDay()->addHours($endHour);

        if ($end->lessThanOrEqualTo($start)) {
            // Support rentang lintas hari, contoh 22 -> 06
            return $now->greaterThanOrEqualTo($start) || $now->lessThan($end->copy()->addDay());
        }

        return $now->greaterThanOrEqualTo($start) && $now->lessThan($end);
    }

    public function nextOffPeakRunAt(): Carbon
    {
        [$startHour, $endHour, $timezone, $bufferMinutes] = $this->config(includeBuffer: true);

        $now = now($timezone);
        $start = $now->copy()->startOfDay()->addHours($startHour);
        $end = $now->copy()->startOfDay()->addHours($endHour);

        if ($end->lessThanOrEqualTo($start)) {
            // Lintas hari: jika masih jam operasional malam, targetkan end besok.
            if ($now->greaterThanOrEqualTo($start)) {
                return $end->copy()->addDay()->addMinutes($bufferMinutes);
            }

            if ($now->lessThan($end)) {
                return $end->copy()->addMinutes($bufferMinutes);
            }

            return $now->copy();
        }

        if ($now->lessThan($start) || $now->greaterThanOrEqualTo($end)) {
            return $now->copy();
        }

        return $end->copy()->addMinutes($bufferMinutes);
    }

    private function config(bool $includeBuffer = false): array
    {
        $start = (int) config('operations.start_hour', 9);
        $end = (int) config('operations.end_hour', 22);
        $timezone = (string) config('operations.timezone', config('app.timezone', 'Asia/Jakarta'));
        $buffer = (int) config('operations.defer_buffer_minutes', 5);

        if ($includeBuffer) {
            return [$start, $end, $timezone, $buffer];
        }

        return [$start, $end, $timezone];
    }
}
