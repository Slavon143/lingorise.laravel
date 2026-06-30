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
        'tts_minutes_per_day', 'tts_minutes_per_month',
        'max_translation_characters',
        'max_explanation_selected_characters',
        'max_explanation_context_characters',
        'max_tts_characters_per_request',
        'requests_per_minute',
        'concurrent_tts_requests',
        'ai_translation_enabled', 'ai_explanation_enabled',
        'ai_tts_enabled', 'browser_tts_enabled',
        'premium_books_enabled', 'private_books_limit',
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
            'max_explanation_selected_characters' => 'integer',
            'max_explanation_context_characters' => 'integer',
            'max_tts_characters_per_request' => 'integer',
            'requests_per_minute' => 'integer',
            'concurrent_tts_requests' => 'integer',
            'ai_translation_enabled' => 'boolean',
            'ai_explanation_enabled' => 'boolean',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'premium_books_enabled' => 'boolean',
            'private_books_limit' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
