<?php

namespace App\Support;

use App\Models\Ingredient;

class IngredientStockView
{
    public static function fromIngredient(Ingredient $ingredient): array
    {
        $stock = (float) $ingredient->converted_stock;
        $minimum = (float) $ingredient->converted_minimum_stock;
        $unit = (string) $ingredient->display_unit;

        $stockPackLabel = null;
        $minimumPackLabel = null;
        $packInfoLabel = null;

        if (($ingredient->display_unit ?? '') === 'pcs' && (int) ($ingredient->pack_size ?? 1) > 1) {
            $packSize = max(1, (int) $ingredient->pack_size);
            $unit = 'pcs';
            $stockPackLabel = self::fullPackCountFromPcs($stock, $packSize) . ' pack';
            $minimumPackLabel = self::fullPackCountFromPcs($minimum, $packSize) . ' pack';
            $packInfoLabel = '1 pack = ' . $packSize . ' pcs';
        }

        $isOut = $stock <= 0;
        $isLow = $stock > 0 && $stock <= $minimum;
        $statusKey = $isOut ? 'out' : ($isLow ? 'low' : 'safe');

        return [
            'stock' => $stock,
            'minimum' => $minimum,
            'unit' => $unit,
            'stock_pack_label' => $stockPackLabel,
            'minimum_pack_label' => $minimumPackLabel,
            'pack_info_label' => $packInfoLabel,
            'stock_text' => self::formatNumber($stock),
            'minimum_text' => self::formatNumber($minimum),
            'progress_percent' => self::stockPercent($stock, $minimum),
            'is_out' => $isOut,
            'is_low' => $isLow,
            'status_key' => $statusKey,
            'status_label' => self::statusLabel($statusKey),
            'status_badge_class' => self::statusBadgeClass($statusKey),
            'value_text_class' => self::valueTextClass($statusKey),
            'progress_class' => self::progressClass($statusKey),
        ];
    }

    private static function statusLabel(string $statusKey): string
    {
        return match ($statusKey) {
            'out' => 'Habis',
            'low' => 'Rendah',
            default => 'Aman',
        };
    }

    private static function statusBadgeClass(string $statusKey): string
    {
        return match ($statusKey) {
            'out' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800/50',
            'low' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50',
            default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50',
        };
    }

    private static function valueTextClass(string $statusKey): string
    {
        return match ($statusKey) {
            'out' => 'text-red-600 dark:text-red-400',
            'low' => 'text-amber-600 dark:text-amber-400',
            default => 'text-blue-600 dark:text-blue-400',
        };
    }

    private static function progressClass(string $statusKey): string
    {
        return match ($statusKey) {
            'out' => 'bg-red-500',
            'low' => 'bg-amber-500',
            default => 'bg-emerald-500',
        };
    }

    private static function formatNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
    }

    private static function fullPackCountFromPcs(float $pcs, int $packSize): int
    {
        $pcsInt = (int) floor(max(0, $pcs));
        $sizeInt = max(1, $packSize);
        return intdiv($pcsInt, $sizeInt);
    }

    private static function stockPercent(float $stock, float $minimum): float
    {
        if ($minimum <= 0) {
            return 100;
        }

        return min(100, ($stock / ($minimum * 2)) * 100);
    }
}
