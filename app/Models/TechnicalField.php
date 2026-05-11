<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class TechnicalField extends Model
{
    protected $fillable = [
        'subcategory_id',
        'label',
        'name',
        'type',
        'order',
    ];

    protected $casts = [
        'label' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class);
    }

    public function getLabelAttribute($value): ?string
    {
        return Translation::text($value);
    }
}
