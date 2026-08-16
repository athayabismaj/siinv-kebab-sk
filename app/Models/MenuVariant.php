<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVariant extends Model
{
    protected $fillable = [
        'menu_id',
        'name',
        'image_path',
        'price',
        'cost_price',
        'sell_price',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_available' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return route('menu-variant-images.show', [
            'filename' => basename($this->image_path),
        ]);
    }
    
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
