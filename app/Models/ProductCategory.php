<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Support\Translation;

class ProductCategory extends Model
{
    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'name',
        'slug',
        'image',
        'order',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
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

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    /*
    |--------------------------------------------------------------------------
    | PALETA DE COLORES
    | Se asignan automáticamente en orden cíclico según el campo `order`.
    | Categoría 1 → color 1, categoría 2 → color 2 ... categoría 9 → color 1 de nuevo.
    |--------------------------------------------------------------------------
    */

    public const COLOR_PALETTE = [
        '#28597D', // 1
        '#FD7F23', // 2
        '#075433', // 3
        '#960D14', // 4
        '#B6AE27', // 5
        '#188495', // 6
        '#431F7F', // 7
        '#3D586D', // 8
    ];

    public const NAMED_COLORS = [
        'caudal' => '#28597D',
        'nivel' => '#FD7F23',
        'presion' => '#075433',
        'temperatura' => '#960D14',
        'analisis' => '#B6AE27',
        'controladores de procesos' => '#188495',
        'indicacion y registro de datos' => '#431F7F',
        'productos especiales y servicios' => '#3D586D',
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
        $stored = $this->attributes['color'] ?? null;

        if (is_string($stored) && preg_match('/^#[0-9A-Fa-f]{6}$/', $stored)) {
            return strtoupper($stored);
        }

        $palette = self::COLOR_PALETTE;

        $order = strtoupper($this->order ?? 'A');

        // convertir letra a número
        $index = ord($order) - ord('A');

        // ciclo infinito
        $index = $index % count($palette);

        return $palette[$index];
    }

    public function getNameAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getDescriptionAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public static function suggestedColor(): string
    {
        $count = static::query()->count();

        return self::COLOR_PALETTE[$count % count(self::COLOR_PALETTE)];
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
                $category->slug = Str::slug(Translation::text($category->getRawOriginal('name'), 'es') ?: $category->name);
            }

            if (!$category->color) {
                $name = Str::of(Translation::text($category->getRawOriginal('name'), 'es') ?: $category->name)->ascii()->lower()->squish()->toString();
                $category->color = self::NAMED_COLORS[$name] ?? self::suggestedColor();
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug(Translation::text($category->getRawOriginal('name'), 'es') ?: $category->name);
            }
        });
    }
}
