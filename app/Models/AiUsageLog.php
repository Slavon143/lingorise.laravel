<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'cache_key',
        'characters_count',
        'cache_hit',
        'model',
        'duration_ms',
        'status',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'cache_hit' => 'boolean',
            'characters_count' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
