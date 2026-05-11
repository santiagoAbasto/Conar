<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class ProductSpecification extends Model
{
    protected $fillable = [
        'source_provider',
        'source_id',
        'source_url',
        'last_import_run_id',
        'product_id',
        'name',
        'value',
        'order',
        'is_active'
    ];

    protected $casts = [
        'name' => 'array',
        'value' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }

    public function getNameAttribute($value): ?string
    {
        return Translation::text($value);
    }

    public function getValueAttribute($value): ?string
    {
        return Translation::text($value);
    }
}
