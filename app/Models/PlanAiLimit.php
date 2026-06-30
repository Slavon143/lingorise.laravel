<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanAiLimit extends Model
{
    protected $fillable = [
        'plan_id',
        'translations_per_day', 'translations_per_month',
        'explanations_per_day', 'explanations_per_month',
        'context_explanations_per_day', 'context_explanations_per_month',
        'grammar_explanations_per_day', 'grammar_explanations_per_month',
        'simplifications_per_day', 'simplifications_per_month',
        'tts_minutes_per_day', 'tts_minutes_per_month',
        'max_translation_characters',
        'max_explanation_selected_characters',
        'max_explanation_context_characters',
        'max_context_explanation_characters',
        'max_grammar_explanation_characters',
        'max_simplification_characters',
        'max_tts_characters_per_request',
        'requests_per_minute',
        'concurrent_tts_requests',
        'ai_translation_enabled', 'ai_explanation_enabled',
        'ai_context_explanation_enabled', 'ai_grammar_explanation_enabled', 'ai_simplification_enabled',
        'ai_tts_enabled', 'browser_tts_enabled',
        'premium_books_enabled', 'private_books_limit',
        'shadowing_enabled',
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
            'max_explanation_selected_characters' => 'integer',
            'max_explanation_context_characters' => 'integer',
            'max_context_explanation_characters' => 'integer',
            'max_grammar_explanation_characters' => 'integer',
            'max_simplification_characters' => 'integer',
            'max_tts_characters_per_request' => 'integer',
            'requests_per_minute' => 'integer',
            'concurrent_tts_requests' => 'integer',
            'ai_translation_enabled' => 'boolean',
            'ai_explanation_enabled' => 'boolean',
            'ai_context_explanation_enabled' => 'boolean',
            'ai_grammar_explanation_enabled' => 'boolean',
            'ai_simplification_enabled' => 'boolean',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'premium_books_enabled' => 'boolean',
            'private_books_limit' => 'integer',
            'shadowing_enabled' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
