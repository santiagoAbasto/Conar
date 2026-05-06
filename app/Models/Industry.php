<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function products()
    {
        return $this->belongsToMany(Product::class, 'industry_product');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        return asset('storage/' . $this->image);
    }
}
