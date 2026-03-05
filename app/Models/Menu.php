<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model {
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];


    /*** RELATIONS */

    // Kategori Menu
    public function category()
    {
        return $this->belongsTo(MenuCategory::class);
    }

    // Variant (Small, Jumbo, dll)
    public function variants()
    {
        return $this->hasMany(MenuVariant::class);
    }

    // Relasi ke transaksi 
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}