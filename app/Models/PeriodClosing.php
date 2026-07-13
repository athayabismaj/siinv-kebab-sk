<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosing extends Model
{
    protected $fillable = [
        'branch_id',
        'period_type',
        'period_date',
        'total_revenue',
        'total_transactions',
        'notes',
        'closed_by_user_id',
    ];

    protected $casts = [
        'period_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_transactions' => 'integer',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
