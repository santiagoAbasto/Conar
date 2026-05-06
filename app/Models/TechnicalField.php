<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalField extends Model
{
    protected $fillable = [
        'subcategory_id',
        'label',
        'name',
        'type',
        'order',
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
}
