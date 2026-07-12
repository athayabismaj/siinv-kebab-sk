<?php

namespace App\Support;

class StockLogTypeMap
{
    public const TAB_RESTOCK = 'in';
    public const TAB_USAGE = 'out';
    public const TAB_RETURN = 'return';
    public const TAB_ADJUSTMENT = 'adjustment';

    /**
     * @return array<int, string>
     */
    public static function allowedTabs(): array
    {
        return [self::TAB_RESTOCK, self::TAB_USAGE, self::TAB_RETURN, self::TAB_ADJUSTMENT];
    }

    /**
     * @return array<int, string>
     */
    public static function tabTypes(string $tab): array
    {
        return match ($tab) {
            self::TAB_RESTOCK => ['in'],
            self::TAB_USAGE => ['out', 'daily_usage', 'transfer_daily'],
            self::TAB_RETURN => ['daily_return'],
            self::TAB_ADJUSTMENT => ['adjustment'],
            default => [],
        };
    }

    public static function tabLabel(?string $tab): string
    {
        return match ($tab) {
            self::TAB_RESTOCK => 'Restok',
            self::TAB_USAGE => 'Pemakaian',
            self::TAB_RETURN => 'Pengembalian',
            self::TAB_ADJUSTMENT => 'Penyesuaian',
            default => 'Semua Riwayat',
        };
    }

    public static function restockCaseSql(): string
    {
        return "SUM(CASE WHEN type = 'in' THEN 1 ELSE 0 END) as restock";
    }

    public static function usageCaseSql(): string
    {
        return "SUM(CASE WHEN type IN ('out', 'daily_usage', 'transfer_daily') THEN 1 ELSE 0 END) as usage";
    }

    public static function returnCaseSql(): string
    {
        return "SUM(CASE WHEN type = 'daily_return' THEN 1 ELSE 0 END) as stock_return";
    }
}
