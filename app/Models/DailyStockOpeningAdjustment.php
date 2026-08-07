<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStockOpeningAdjustment extends Model
{
    protected $fillable = [
        'daily_stock_session_id',
        'daily_stock_item_id',
        'ingredient_id',
        'expected_qty',
        'actual_qty',
        'difference_qty',
        'created_by',
        'note',
    ];

    protected $casts = [
        'expected_qty' => 'decimal:2',
        'actual_qty' => 'decimal:2',
        'difference_qty' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DailyStockSession::class, 'daily_stock_session_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DailyStockItem::class, 'daily_stock_item_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
