<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'reference_id',
        'note'
    ];

    protected $casts = [
        'quantity' => 'decimal:2'
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'in' => 'Restok',
            'out' => 'Pemakaian',
            'adjustment' => 'Penyesuaian',
            default => 'Unknown'
        };
    }
}