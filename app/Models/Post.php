<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Support\Translation;

class Post extends Model
{
    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'slug',
        'image',
        'category',
        'published_at',
        'is_active',
        'is_featured',
        'order',
    ];

    protected $casts = [
        'title'        => 'array',
        'excerpt'      => 'array',
        'content'      => 'array',
        'category'     => 'array',
        'published_at' => 'datetime',
        'is_active'    => 'boolean',
        'is_featured'  => 'boolean',
    ];

    protected $appends = [
        'image_url'
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($post) {

            if (!$post->slug) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return asset('images/placeholder-post.png');
        }

        if (Str::startsWith($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    /*
    |--------------------------------------------------------------------------
    | Limpieza de texto (para JSON mal guardado)
    |--------------------------------------------------------------------------
    */

    private function cleanJsonText($value)
    {
        if (!$value) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {

            if (is_array($decoded)) {
                return Translation::text($decoded);
            }

            if (is_string($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    public function getTitleAttribute($value)
    {
        return Translation::text($this->cleanJsonText($value));
    }

    public function getExcerptAttribute($value)
    {
        return Translation::text($this->cleanJsonText($value));
    }

    public function getContentAttribute($value)
    {
        $clean = Translation::text($this->cleanJsonText($value));

        // eliminar etiquetas HTML peligrosas o innecesarias
        return strip_tags($clean, '<p><br>');
    }

    public function getCategoryAttribute($value)
    {
        return Translation::text($this->cleanJsonText($value));
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc');
    }
}
