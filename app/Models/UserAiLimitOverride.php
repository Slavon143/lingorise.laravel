<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiLimitOverride extends Model
{
    protected $fillable = [
        'user_id',
        'translations_per_day', 'translations_per_month',
        'explanations_per_day', 'explanations_per_month',
        'tts_minutes_per_day', 'tts_minutes_per_month',
        'max_translation_characters',
        'max_explanation_context_characters',
        'max_tts_characters_per_request',
        'ai_translation_enabled', 'ai_explanation_enabled',
        'ai_tts_enabled', 'browser_tts_enabled',
        'starts_at', 'ends_at', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'translations_per_day' => 'integer',
            'translations_per_month' => 'integer',
            'explanations_per_day' => 'integer',
            'explanations_per_month' => 'integer',
            'tts_minutes_per_day' => 'integer',
            'tts_minutes_per_month' => 'integer',
            'max_translation_characters' => 'integer',
            'max_explanation_context_characters' => 'integer',
            'max_tts_characters_per_request' => 'integer',
            'ai_translation_enabled' => 'boolean',
            'ai_explanation_enabled' => 'boolean',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
