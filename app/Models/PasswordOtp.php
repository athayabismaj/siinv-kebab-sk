<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp_hash',
        'expires_at',
        'attempts',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}