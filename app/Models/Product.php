<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'category_id',
        'subcategory_id',
        'name',
        'slug',
        'subtitle',
        'brand',
        'brand_logo',
        'short_description',
        'description',
        'image',
        'video',
        'video_url',
        'order',
        'is_featured',
        'is_active',
        'show_in_home',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'is_active'    => 'boolean',
        'show_in_home' => 'boolean',
    ];

    protected $with = ['category'];

    protected $attributes = [
        'is_active' => true
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)
            ->orderBy('order');
    }

    public function downloads()
    {
        return $this->hasMany(ProductDownload::class);
    }

    public function industries()
    {
        return $this->belongsToMany(
            Industry::class,
            'industry_product'
        );
    }

    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class);
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return $this->mediaUrl($this->image);
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SLUG
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($product) {

            if (!$product->slug) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
