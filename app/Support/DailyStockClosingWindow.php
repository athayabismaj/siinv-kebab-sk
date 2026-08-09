<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class DailyStockClosingWindow
{
    public static function cutoffHour(): int
    {
        return max(0, min(23, (int) config('operations.daily_stock_close_grace_hour', 3)));
    }

    public static function isPreviousDayGracePeriod(?CarbonInterface $time = null): bool
    {
        return self::localTime($time)->hour < self::cutoffHour();
    }

    /**
     * @return array<int, string>
     */
    public static function candidateSessionDates(?CarbonInterface $time = null): array
    {
        $localTime = self::localTime($time);
        $today = $localTime->toDateString();

        if (! self::isPreviousDayGracePeriod($localTime)) {
            return [$today];
        }

        return [
            $localTime->subDay()->toDateString(),
            $today,
        ];
    }

    public static function cutoffLabel(): string
    {
        return sprintf('%02d:00', self::cutoffHour());
    }

    private static function localTime(?CarbonInterface $time = null): CarbonImmutable
    {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');

        return $time
            ? CarbonImmutable::instance($time)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);
    }
}
