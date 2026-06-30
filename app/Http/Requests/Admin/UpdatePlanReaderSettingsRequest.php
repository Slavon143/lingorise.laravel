<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanReaderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'price_currency' => ['nullable', 'string', 'size:3'],
            'billing_interval' => ['nullable', Rule::in(['month', 'year'])],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],

            'translations_per_day' => ['nullable', 'integer', 'min:0'],
            'translations_per_month' => ['nullable', 'integer', 'min:0'],
            'explanations_per_day' => ['nullable', 'integer', 'min:0'],
            'explanations_per_month' => ['nullable', 'integer', 'min:0'],
            'context_explanations_per_day' => ['nullable', 'integer', 'min:0'],
            'context_explanations_per_month' => ['nullable', 'integer', 'min:0'],
            'grammar_explanations_per_day' => ['nullable', 'integer', 'min:0'],
            'grammar_explanations_per_month' => ['nullable', 'integer', 'min:0'],
            'simplifications_per_day' => ['nullable', 'integer', 'min:0'],
            'simplifications_per_month' => ['nullable', 'integer', 'min:0'],
            'tts_minutes_per_day' => ['nullable', 'integer', 'min:0'],
            'tts_minutes_per_month' => ['nullable', 'integer', 'min:0'],
            'max_translation_characters' => ['required', 'integer', 'min:0'],
            'max_explanation_selected_characters' => ['nullable', 'integer', 'min:0'],
            'max_explanation_context_characters' => ['required', 'integer', 'min:0'],
            'max_context_explanation_characters' => ['nullable', 'integer', 'min:0'],
            'max_grammar_explanation_characters' => ['nullable', 'integer', 'min:0'],
            'max_simplification_characters' => ['nullable', 'integer', 'min:0'],
            'max_tts_characters_per_request' => ['required', 'integer', 'min:0'],
            'requests_per_minute' => ['required', 'integer', 'min:0'],
            'concurrent_tts_requests' => ['required', 'integer', 'min:0'],
            'private_books_limit' => ['nullable', 'integer', 'min:0'],

            'translation_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'context_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'grammar_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'simplify_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'tts_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'pronunciation_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'vocabulary_max_words' => ['required', 'integer', 'min:1', 'max:200'],
            'ai_actions_daily_limit' => ['required', 'integer', 'min:0'],
            'ai_actions_monthly_limit' => ['nullable', 'integer', 'min:0'],
            'ai_tts_monthly_characters' => ['nullable', 'integer', 'min:0'],
            'vocabulary_entries_limit' => ['nullable', 'integer', 'min:1'],
            'reader_private_books_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
