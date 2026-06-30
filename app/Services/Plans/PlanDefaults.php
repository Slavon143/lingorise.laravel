<?php

namespace App\Services\Plans;

use App\Enums\PlanCode;

class PlanDefaults
{
    public static function for(string $code): array
    {
        return match ($code) {
            PlanCode::Premium->value => self::premium(),
            PlanCode::Pro->value => self::pro(),
            default => self::free(),
        };
    }

    public static function free(): array
    {
        return [
            'translation_max_words' => 10,
            'context_max_words' => 6,
            'grammar_max_words' => 10,
            'simplify_max_words' => 10,
            'tts_max_words' => 10,
            'pronunciation_max_words' => 10,
            'vocabulary_max_words' => 10,
            'ai_actions_daily_limit' => 10,
            'ai_actions_monthly_limit' => 200,
            'ai_tts_monthly_characters' => 0,
            'ai_tts_enabled' => false,
            'browser_tts_enabled' => true,
            'pronunciation_recording_enabled' => true,
            'shadowing_enabled' => false,
            'voice_selection_enabled' => false,
            'context_enabled' => true,
            'grammar_enabled' => true,
            'simplify_enabled' => true,
            'translation_enabled' => true,
            'vocabulary_enabled' => true,
            'daily_goal_enabled' => true,
            'streak_enabled' => true,
            'import_private_books_enabled' => true,
            'public_library_enabled' => true,
            'vocabulary_entries_limit' => 100,
            'private_books_limit' => 3,
            'is_active' => true,
        ];
    }

    public static function premium(): array
    {
        return [
            'translation_max_words' => 30,
            'context_max_words' => 20,
            'grammar_max_words' => 30,
            'simplify_max_words' => 30,
            'tts_max_words' => 30,
            'pronunciation_max_words' => 25,
            'vocabulary_max_words' => 20,
            'ai_actions_daily_limit' => 100,
            'ai_actions_monthly_limit' => 2500,
            'ai_tts_monthly_characters' => 50000,
            'ai_tts_enabled' => true,
            'browser_tts_enabled' => true,
            'pronunciation_recording_enabled' => true,
            'shadowing_enabled' => true,
            'voice_selection_enabled' => false,
            'context_enabled' => true,
            'grammar_enabled' => true,
            'simplify_enabled' => true,
            'translation_enabled' => true,
            'vocabulary_enabled' => true,
            'daily_goal_enabled' => true,
            'streak_enabled' => true,
            'import_private_books_enabled' => true,
            'public_library_enabled' => true,
            'vocabulary_entries_limit' => 5000,
            'private_books_limit' => 50,
            'is_active' => true,
        ];
    }

    public static function pro(): array
    {
        return [
            'translation_max_words' => 50,
            'context_max_words' => 30,
            'grammar_max_words' => 50,
            'simplify_max_words' => 40,
            'tts_max_words' => 50,
            'pronunciation_max_words' => 30,
            'vocabulary_max_words' => 25,
            'ai_actions_daily_limit' => 300,
            'ai_actions_monthly_limit' => 8000,
            'ai_tts_monthly_characters' => 200000,
            'ai_tts_enabled' => true,
            'browser_tts_enabled' => true,
            'pronunciation_recording_enabled' => true,
            'shadowing_enabled' => true,
            'voice_selection_enabled' => true,
            'context_enabled' => true,
            'grammar_enabled' => true,
            'simplify_enabled' => true,
            'translation_enabled' => true,
            'vocabulary_enabled' => true,
            'daily_goal_enabled' => true,
            'streak_enabled' => true,
            'import_private_books_enabled' => true,
            'public_library_enabled' => true,
            'vocabulary_entries_limit' => null,
            'private_books_limit' => null,
            'is_active' => true,
        ];
    }
}
