<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisPaymentAttempt extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_REPLACED = 'REPLACED';

    protected $fillable = [
        'transaction_id',
        'reference',
        'amount',
        'payload_hash',
        'status',
        'generated_at',
        'expires_at',
        'confirmed_at',
        'confirmed_by',
        'confirmation_source',
        'provider_reference',
    ];

    protected $hidden = [
        'payload_hash',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by')->withTrashed();
    }
}
