<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationCache extends Model
{
    protected $table = 'translation_cache';

    protected $fillable = [
        'cache_key',
        'source_text',
        'source_language',
        'target_language',
        'translated_text',
        'pronunciation',
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
