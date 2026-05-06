<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function subcategories()
    {
        return $this->hasMany(ProductSubcategory::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | PALETA DE COLORES
    | Se asignan automáticamente en orden cíclico según el campo `order`.
    | Categoría 1 → color 1, categoría 2 → color 2 ... categoría 9 → color 1 de nuevo.
    |--------------------------------------------------------------------------
    */

    const COLOR_PALETTE = [
        '#28597D', // 1
        '#FD7F23', // 2
        '#075433', // 3
        '#960D14', // 4
        '#B6AE27', // 5
        '#188495', // 6
        '#431F7F', // 7
        '#3D586D', // 8
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Color automático basado en la posición (order).
     * Se usa en vistas Blade para el sidebar y las tarjetas de productos.
     */
    public function getColorAttribute(): string
    {
        $palette = self::COLOR_PALETTE;

        $order = strtoupper($this->order ?? 'A');

        // convertir letra a número
        $index = ord($order) - ord('A');

        // ciclo infinito
        $index = $index % count($palette);

        return $palette[$index];
    }


    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SLUG
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
