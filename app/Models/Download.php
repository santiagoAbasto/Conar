<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'is_active' => 'boolean',
    ];

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file) return null;
        return asset('storage/' . $this->file);
    }
}
