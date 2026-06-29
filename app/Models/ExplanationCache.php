<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExplanationCache extends Model
{
    protected $table = 'explanation_cache';

    protected $fillable = [
        'cache_key',
        'selected_text',
        'context_text',
        'source_language',
        'target_language',
        'explanation_text',
        'model',
        'prompt_version',
        'hits',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_version' => 'integer',
            'hits' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }
}
