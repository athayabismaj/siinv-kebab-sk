<?php

namespace App\Support;

class IngredientUnit
{
    public static function toBase(string $unit, float $value): float
    {
        return match (strtolower(trim($unit))) {
            'kg', 'l' => $value * 1000,
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
}
