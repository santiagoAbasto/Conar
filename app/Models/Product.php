<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
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

        return Storage::url($this->image);
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
