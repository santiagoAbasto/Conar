<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lastImportRun()
    {
        return $this->belongsTo(ImportRun::class, 'last_import_run_id');
    }
}
