<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id',
        'menu_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class)->withTrashed();
    }
}
