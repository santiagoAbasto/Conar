<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRunItem extends Model
{
    protected $fillable = [
        'import_run_id',
        'entity_type',
        'entity_id',
        'action',
        'source_key',
        'entity_label',
        'before_state',
        'after_state',
        'meta',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'meta' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class, 'import_run_id');
    }
}
