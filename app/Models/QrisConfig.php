<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisConfig extends Model
{
    protected $fillable = [
        'branch_id',
        'merchant_name',
        'merchant_display_name',
        'merchant_city',
        'qris_payload',
        'is_active',
        'activated_at',
        'deactivated_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'qris_payload',
    ];

    protected function casts(): array
    {
        return [
            'qris_payload' => 'encrypted',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
