<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRun extends Model
{
    protected $fillable = [
        'source_provider',
        'source_type',
        'scope',
        'source_url',
        'options',
        'counters',
        'status',
        'dry_run',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'options' => 'array',
        'counters' => 'array',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ImportRunItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportRunLog::class);
    }
}
