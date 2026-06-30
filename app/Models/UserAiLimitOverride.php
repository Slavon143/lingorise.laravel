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
        'context_explanations_per_day', 'context_explanations_per_month',
        'grammar_explanations_per_day', 'grammar_explanations_per_month',
        'simplifications_per_day', 'simplifications_per_month',
        'tts_minutes_per_day', 'tts_minutes_per_month',
        'max_translation_characters',
        'max_explanation_context_characters',
        'max_context_explanation_characters',
        'max_grammar_explanation_characters',
        'max_simplification_characters',
        'max_tts_characters_per_request',
        'ai_translation_enabled', 'ai_explanation_enabled',
        'ai_context_explanation_enabled', 'ai_grammar_explanation_enabled', 'ai_simplification_enabled',
        'ai_tts_enabled', 'browser_tts_enabled',
        'shadowing_enabled',
        'starts_at', 'ends_at', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'translations_per_day' => 'integer',
            'translations_per_month' => 'integer',
            'explanations_per_day' => 'integer',
            'explanations_per_month' => 'integer',
            'context_explanations_per_day' => 'integer',
            'context_explanations_per_month' => 'integer',
            'grammar_explanations_per_day' => 'integer',
            'grammar_explanations_per_month' => 'integer',
            'simplifications_per_day' => 'integer',
            'simplifications_per_month' => 'integer',
            'tts_minutes_per_day' => 'integer',
            'tts_minutes_per_month' => 'integer',
            'max_translation_characters' => 'integer',
            'max_explanation_context_characters' => 'integer',
            'max_context_explanation_characters' => 'integer',
            'max_grammar_explanation_characters' => 'integer',
            'max_simplification_characters' => 'integer',
            'max_tts_characters_per_request' => 'integer',
            'ai_translation_enabled' => 'boolean',
            'ai_explanation_enabled' => 'boolean',
            'ai_context_explanation_enabled' => 'boolean',
            'ai_grammar_explanation_enabled' => 'boolean',
            'ai_simplification_enabled' => 'boolean',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'shadowing_enabled' => 'boolean',
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
