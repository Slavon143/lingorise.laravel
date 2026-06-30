<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiStructuredCache extends Model
{
    protected $table = 'ai_structured_cache';

    protected $fillable = [
        'cache_key',
        'operation_type',
        'source_text',
        'source_text_hash',
        'context_text',
        'context_hash',
        'source_language',
        'target_language',
        'target_level',
        'response_json',
        'model',
        'provider',
        'prompt_version',
        'response_format_version',
        'privacy_scope',
        'scope_id',
        'hits',
        'last_used_at',
        'original_usage_event_id',
    ];

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'hits' => 'integer',
            'last_used_at' => 'datetime',
            'prompt_version' => 'integer',
            'response_format_version' => 'integer',
        ];
    }
}
