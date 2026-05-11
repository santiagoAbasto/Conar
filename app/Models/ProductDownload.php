<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class ProductDownload extends Model
{
    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'product_id',
        'name',
        'file',
        'type',
        'size',
        'order',
        'is_active',
        'is_principal',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active'    => 'boolean',
        'is_principal' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file);
    }

    public function getNameAttribute($value): ?string
    {
        return Translation::text($value);
    }
}
