<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image',
        'video_url',
        'order'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }

    public function isVideo(): bool
    {
        return !empty($this->video_url);
    }

    /**
     * Returns embeddable URL:
     * - YouTube → https://www.youtube.com/embed/{id}
     * - Vimeo   → https://player.vimeo.com/video/{id}
     * - mp4     → storage URL as-is
     */
    public function getEmbedUrlAttribute(): string
    {
        $url = $this->video_url ?? '';

        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        // Local mp4
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return asset('storage/' . $url);
    }

    /**
     * Thumbnail for video: YouTube poster or play icon fallback
     */
    public function getVideoThumbAttribute(): ?string
    {
        $url = $this->video_url ?? '';

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
        }

        return null;
    }
}