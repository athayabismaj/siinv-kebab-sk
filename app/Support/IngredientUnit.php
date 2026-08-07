<?php

namespace App\Support;

class IngredientUnit
{
    private const QUANTITY_SCALE = 2;

    public static function toBase(string $unit, float $value): float
    {
        return match (strtolower(trim($unit))) {
            'kg', 'l' => $value * 1000,
            default => $value,
        };
    }

    public static function toDisplay(string $unit, float $value): float
    {
        return match (strtolower(trim($unit))) {
            'kg', 'l' => $value / 1000,
            default => $value,
        };
    }

    public static function baseUnit(string $displayUnit): string
    {
        return match (strtolower(trim($displayUnit))) {
            'kg', 'g' => 'g',
            'l', 'ml' => 'ml',
            'pcs' => 'pcs',
            default => strtolower(trim($displayUnit)),
        };
    }

    public static function requiresWholeQuantity(string $unit): bool
    {
        return self::baseUnit($unit) === 'pcs';
    }

    public static function isValidBaseQuantity(string $unit, float $value): bool
    {
        if (! is_finite($value) || $value < 0) {
            return false;
        }

        if (self::requiresWholeQuantity($unit)) {
            return abs($value - round($value)) < 0.000001;
        }

        return abs($value - round($value, self::QUANTITY_SCALE)) < 0.000001;
    }

    public static function normalizeBaseQuantity(string $unit, float $value): float
    {
        return self::requiresWholeQuantity($unit)
            ? round($value)
            : round($value, self::QUANTITY_SCALE);
    }

    public static function packInputStep(int $packSize): float
    {
        return round(1 / max(1, $packSize), 6);
    }
}
