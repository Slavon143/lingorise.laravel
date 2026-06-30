<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Global on/off switches for intelligence features.
    | When disabled, endpoints return 503 with feature_disabled error code.
    |
    */
    'context_explanation_enabled' => env('CONTEXT_EXPLANATION_ENABLED', true),
    'grammar_explanation_enabled' => env('GRAMMAR_EXPLANATION_ENABLED', true),
    'simplification_enabled' => env('SIMPLIFICATION_ENABLED', true),
    'advanced_vocabulary_enabled' => env('ADVANCED_VOCABULARY_ENABLED', true),
    'shadowing_enabled' => env('SHADOWING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Limits
    |--------------------------------------------------------------------------
    |
    | Fallback limits when plan_ai_limits are not configured.
    | These are overridden by plan_ai_limits records.
    |
    */
    'defaults' => [
        'context_explanations_per_day' => 5,
        'grammar_explanations_per_day' => 3,
        'simplifications_per_day' => 3,
        'max_context_explanation_characters' => 1000,
        'max_grammar_explanation_characters' => 1500,
        'max_simplification_characters' => 4000,
        'shadowing_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Target Languages
    |--------------------------------------------------------------------------
    |
    | Whitelist for target_language override in requests.
    | The user's languagePreference.native_locale is always accepted.
    |
    */
    'allowed_target_locales' => [
        'de', 'en', 'sv', 'ru',
    ],
];
