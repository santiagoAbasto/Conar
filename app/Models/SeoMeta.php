<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SeoMeta extends Model
{
    protected $fillable = [
        'page',
        'title',
        'description',
        'keywords',
        'og_image',
    ];

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image ? url(Storage::url($this->og_image)) : null;
    }
}
