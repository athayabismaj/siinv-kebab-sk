<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AdminCache
{
    public static function key(string $domain, string $suffix): string
    {
        return sprintf('admin:%s:v%d:%s', $domain, self::version($domain), $suffix);
    }

    public static function version(string $domain): int
    {
        return (int) Cache::get(self::versionKey($domain), 1);
    }

    public static function bump(string $domain): int
    {
        $key = self::versionKey($domain);

        if (! Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return (int) Cache::increment($key);
    }

    public static function bumpDashboard(): int
    {
        return self::bump('dashboard');
    }

    public static function bumpCashflow(): int
    {
        return self::bump('cashflow');
    }

    public static function bumpUsage(): int
    {
        return self::bump('usage');
    }

    private static function versionKey(string $domain): string
    {
        return 'admin:cache_version:' . $domain;
    }
}