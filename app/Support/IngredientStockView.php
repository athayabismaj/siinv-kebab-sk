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
            'is_out' => $stock <= 0,
            'is_low' => $stock > 0 && $stock <= $minimum,
        ];
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