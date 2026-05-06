<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'is_active',
        'order'
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'is_active' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('order');
    }
}