<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'product_id',
        'image',
        'video_url',
        'order'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->mediaUrl($this->image);
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
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return $this->mediaUrl($url);
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

    private function mediaUrl(?string $path): ?string
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
}
