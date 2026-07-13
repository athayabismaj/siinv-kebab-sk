<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTarget extends Model
{
    protected $fillable = [
        'branch_id',
        'target_date',
        'target_revenue',
        'target_transactions',
        'notes',
        'set_by_user_id',
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_revenue' => 'decimal:2',
        'target_transactions' => 'integer',
    ];

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
