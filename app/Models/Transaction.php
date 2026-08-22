<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';

    public const STATUS_SUCCESS = 'SUCCESS';

    protected $fillable = [
        'transaction_code',
        'branch_id',
        'user_id',
        'total_amount',
        'payment_method_id',
        'paid_amount',
        'change_amount',
        'status',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_confirmation_source',
        'payment_provider_reference',
        'daily_stock_session_id',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'voided_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function qrisPaymentAttempts(): HasMany
    {
        return $this->hasMany(QrisPaymentAttempt::class);
    }

    public function paymentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by')->withTrashed();
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }

    /**
     * Sales metrics are based only on completed checkout transactions.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }
}
