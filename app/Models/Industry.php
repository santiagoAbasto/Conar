<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class Industry extends Model
{
    protected $fillable = [
        'title',
        'name',
        'slug',
        'description',
        'image',
        'order',
        'is_active'
    ];

    protected $casts = [
        'title' => 'array',
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'industry_product');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        return asset('storage/' . $this->image);
    }

    public function getTitleAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getNameAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getDescriptionAttribute($value): ?string
    {
        return Translation::text($value);
    }
}
