<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySalesSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'sale_date',
        'total_transactions',
        'total_revenue',
        'total_items_sold',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_transactions' => 'integer',
        'total_revenue' => 'decimal:2',
        'total_items_sold' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
