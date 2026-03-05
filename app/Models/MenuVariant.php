<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVariant extends Model
{
    protected $fillable = [
        'menu_id',
        'name',
        'price',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'sort_order' => 'integer',
        'is_available' => 'boolean',
    ];
    
    // Relasi ke Menu
    public function menu()
    {
        return $this->belongsTo(Menu::class)->withTrashed();
    }

    // Relasi ke Ingredient (untuk resep nanti)
    public function ingredients() {
        return $this->belongsToMany(Ingredient::class,'menu_variant_ingredients', 'menu_variant_id', 'ingredient_id')->withPivot('quantity')->withTimestamps();
    }
}