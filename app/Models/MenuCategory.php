<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = ['name', 'is_addon'];

    protected $casts = [
        'is_addon' => 'boolean',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class, 'category_id');
    }
}
