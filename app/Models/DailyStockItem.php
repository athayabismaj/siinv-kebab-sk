<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStockItem extends Model
{
    protected $fillable = [
        'daily_stock_session_id',
        'ingredient_id',
        'carry_forward_qty',
        'opening_adjustment_qty',
        'transferred_qty',
        'opening_qty',
        'remaining_qty',
        'used_qty',
        'returned_qty',
        'carry_forward_reconciled_at',
        'carry_forward_reconciled_by',
        'note',
    ];

    protected $casts = [
        'carry_forward_qty' => 'decimal:2',
        'opening_adjustment_qty' => 'decimal:2',
        'transferred_qty' => 'decimal:2',
        'opening_qty' => 'decimal:2',
        'remaining_qty' => 'decimal:2',
        'used_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
        'carry_forward_reconciled_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DailyStockSession::class, 'daily_stock_session_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function carryForwardReconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'carry_forward_reconciled_by');
    }
}
