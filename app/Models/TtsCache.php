<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtsCache extends Model
{
    protected $table = 'tts_cache';

    protected $fillable = [
        'cache_key',
        'source_text',
        'language',
        'voice',
        'speed',
        'model',
        'format',
        'file_path',
        'duration_ms',
        'file_size',
        'hits',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'file_size' => 'integer',
            'hits' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }
}
