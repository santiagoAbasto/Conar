<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class Download extends Model
{
    protected $fillable = [
        'name',
        'file',
        'category',
        'description',
        'size',
        'order',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'category' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
    ];

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file) return null;
        return asset('storage/' . $this->file);
    }

    public function getNameAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getCategoryAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getDescriptionAttribute($value): ?string
    {
        return Translation::text($value);
    }
}
