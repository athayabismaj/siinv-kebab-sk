<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'description',
        'image_path'
    ];

    // Relasi ke transaksi
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}