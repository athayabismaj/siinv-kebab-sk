<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'display_unit',
        'base_unit',
        'stock',
        'minimum_stock'
    ];


    public function category() {
        return $this->belongsTo(IngredientCategory::class);
    }


    public function menus() {
        return $this->belongsToMany(Menu::class, 'menu_ingredients')->withPivot('quantity')->withTimestamps();
    }

    public function getConvertedStockAttribute() {
        if (in_array($this->display_unit, ['kg', 'l'])) {
            return $this->stock / 1000;
        }

        return $this->stock;
    }

    public function getConvertedMinimumStockAttribute() {
        if (in_array($this->display_unit, ['kg', 'l'])) {
            return $this->minimum_stock / 1000;
        }

        return $this->minimum_stock;
    }

    public function getDisplayStockValueAttribute()
    {
        $metrics = $this->getDisplayMetrics();

        return $this->formatDisplayNumber($metrics['stock']);
    }

    public function getDisplayMinimumStockValueAttribute()
    {
        $metrics = $this->getDisplayMetrics();

        return $this->formatDisplayNumber($metrics['minimum_stock']);
    }

    public function getDisplayStockUnitAttribute()
    {
        $metrics = $this->getDisplayMetrics();

        return $metrics['unit'];
    }

    private function getDisplayMetrics(): array
    {
        $stock = (float) $this->stock;
        $minimumStock = (float) $this->minimum_stock;
        $unit = strtolower(trim((string) $this->base_unit));

        $gramUnits = ['g', 'gr', 'gram', 'grams'];
        $mlUnits = ['ml', 'milliliter', 'milliliters'];
        $kgUnits = ['kg', 'kilogram', 'kilograms'];
        $literUnits = ['l', 'lt', 'liter', 'liters'];

        if (in_array($unit, $gramUnits, true)) {
            if ($stock >= 1000) {
                $stock /= 1000;
                $minimumStock /= 1000;
                $unit = 'kg';
            } else {
                $unit = 'g';
            }
        } elseif (in_array($unit, $kgUnits, true)) {
            $unit = 'kg';
        } elseif (in_array($unit, $mlUnits, true)) {
            if ($stock >= 1000) {
                $stock /= 1000;
                $minimumStock /= 1000;
                $unit = 'l';
            } else {
                $unit = 'ml';
            }
        } elseif (in_array($unit, $literUnits, true)) {
            $unit = 'l';
        }

        return [
            'stock' => $stock,
            'minimum_stock' => $minimumStock,
            'unit' => $unit,
        ];
    }

    private function formatDisplayNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }

    
    public function stockLogs() {
        return $this->hasMany(StockLog::class);
    }



}
