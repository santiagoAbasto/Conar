<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'media_type',
        'media',
        'video_url',
        'button_text',
        'button_link',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | CLEAN CMS TEXT (ANTI &nbsp; / HTML BASURA)
    |--------------------------------------------------------------------------
    */

    private function cleanText($value)
    {
        if (!$value) return null;

        // eliminar &nbsp;
        $value = str_replace('&nbsp;', ' ', $value);

        // eliminar párrafos vacíos
        $value = preg_replace('/<p>\s*<\/p>/', '', $value);

        // eliminar espacios sobrantes
        $value = trim($value);

        return $value;
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $this->cleanText($value);
    }

    public function setSubtitleAttribute($value)
    {
        $this->attributes['subtitle'] = $this->cleanText($value);
    }

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $this->cleanText($value);
    }

    public function setButtonTextAttribute($value)
    {
        $this->attributes['button_text'] = $this->cleanText($value);
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA
    |--------------------------------------------------------------------------
    */

    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media) return null;
        return asset('storage/' . $this->media);
    }
}