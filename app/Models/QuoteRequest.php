<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    protected $fillable = [
        'instrumental',
        'solution',
        'quantity',
        'attachment',
        'process_data',
        'name',
        'email',
        'phone',
        'company',
        'message',
        'is_read'
    ];

    protected $casts = [
        'process_data' => 'array',
    ];
}
