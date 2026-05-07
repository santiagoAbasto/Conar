<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductSubcategory extends Model
{

    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'name',
        'slug',
        'category_id',
        'image',
        'order',
        'is_active',
        'show_in_home',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'show_in_home' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    public function technicalFields()
    {
        return $this->hasMany(TechnicalField::class, 'subcategory_id')->orderBy('order');
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SLUG
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($subcategory) {
            if (!$subcategory->slug) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });

        static::updating(function ($subcategory) {
            if ($subcategory->isDirty('name') && !$subcategory->isDirty('slug')) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });
    }
}
