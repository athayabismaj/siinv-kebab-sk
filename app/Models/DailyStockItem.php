<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStockItem extends Model
{
    protected $fillable = [
        'daily_stock_session_id',
        'ingredient_id',
        'opening_qty',
        'remaining_qty',
        'used_qty',
        'returned_qty',
        'note',
    ];

    protected $casts = [
        'opening_qty' => 'decimal:2',
        'remaining_qty' => 'decimal:2',
        'used_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DailyStockSession::class, 'daily_stock_session_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

