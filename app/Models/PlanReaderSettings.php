<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanReaderSettings extends Model
{
    protected $fillable = [
        'plan_id',
        'translation_max_words',
        'context_max_words',
        'grammar_max_words',
        'simplify_max_words',
        'tts_max_words',
        'pronunciation_max_words',
        'vocabulary_max_words',
        'ai_actions_daily_limit',
        'ai_tts_monthly_characters',
        'ai_tts_enabled',
        'browser_tts_enabled',
        'pronunciation_recording_enabled',
        'shadowing_enabled',
        'voice_selection_enabled',
        'context_enabled',
        'grammar_enabled',
        'simplify_enabled',
        'translation_enabled',
        'vocabulary_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ai_tts_monthly_characters' => 'integer',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'pronunciation_recording_enabled' => 'boolean',
            'shadowing_enabled' => 'boolean',
            'voice_selection_enabled' => 'boolean',
            'context_enabled' => 'boolean',
            'grammar_enabled' => 'boolean',
            'simplify_enabled' => 'boolean',
            'translation_enabled' => 'boolean',
            'vocabulary_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}