<?php

namespace App\Support;

class UsageQuantityFormatter
{
    public static function formatLabel(float $totalQuantity, string $baseUnit, string $displayUnit, int $packSize): string
    {
        $parts = self::parts($totalQuantity, $baseUnit, $displayUnit, $packSize);

        return $parts['full'];
    }

    public static function parts(float $totalQuantity, string $baseUnit, string $displayUnit, int $packSize): array
    {
        $baseUnit = strtolower(trim($baseUnit));
        $displayUnit = strtolower(trim($displayUnit));

        if ($displayUnit === 'pcs') {
            $pcs = self::num($totalQuantity);
            $packLabel = '';

            $packSize = max(1, $packSize);
            if ($packSize > 1) {
                $packLabel = self::num($totalQuantity / $packSize) . ' pack';
            }

            return [
                'quantity' => $pcs . ' pcs',
                'pack' => $packLabel,
                'full' => $packLabel !== '' ? ($pcs . ' pcs (' . $packLabel . ')') : ($pcs . ' pcs'),
            ];
        }

        $converted = $totalQuantity;
        $unitLabel = $baseUnit;

        if (in_array($baseUnit, ['g', 'gr', 'gram'], true)) {
            if ($totalQuantity >= 1000) {
                $converted = $totalQuantity / 1000;
                $unitLabel = 'kg';
            } else {
                $unitLabel = 'g';
            }
        } elseif (in_array($baseUnit, ['ml', 'milliliter'], true)) {
            if ($totalQuantity >= 1000) {
                $converted = $totalQuantity / 1000;
                $unitLabel = 'l';
            } else {
                $unitLabel = 'ml';
            }
        }

        $value = self::num($converted);

        return [
            'quantity' => $value . ' ' . $unitLabel,
            'pack' => '',
            'full' => $value . ' ' . $unitLabel,
        ];
    }

    private static function num(float $value): string
    {
        $trimmed = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }
}
