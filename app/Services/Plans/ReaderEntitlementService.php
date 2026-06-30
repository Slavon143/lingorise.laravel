<?php

namespace App\Services\Plans;

use App\Enums\AiOperationType;
use App\Models\AiUsageEvent;
use App\Models\Plan;
use App\Models\PlanReaderSettings;
use App\Models\User;
use App\Services\Intelligence\Subscription\SubscriptionResolver;

class ReaderEntitlementService
{
    private const FEATURE_FLAGS = [
        'translation' => 'translation_enabled',
        'context' => 'context_enabled',
        'grammar' => 'grammar_enabled',
        'simplify' => 'simplify_enabled',
        'vocabulary' => 'vocabulary_enabled',
        'daily_goal' => 'daily_goal_enabled',
        'streak' => 'streak_enabled',
        'import_private_books' => 'import_private_books_enabled',
        'public_library' => 'public_library_enabled',
        'browser_tts' => 'browser_tts_enabled',
        'ai_tts' => 'ai_tts_enabled',
        'pronunciation' => 'pronunciation_recording_enabled',
        'shadowing' => 'shadowing_enabled',
        'voice_selection' => 'voice_selection_enabled',
    ];

    private const WORD_LIMITS = [
        'translation' => 'translation_max_words',
        'context' => 'context_max_words',
        'grammar' => 'grammar_max_words',
        'simplify' => 'simplify_max_words',
        'tts' => 'tts_max_words',
        'pronunciation' => 'pronunciation_max_words',
        'vocabulary' => 'vocabulary_max_words',
    ];

    public function __construct(
        private readonly SubscriptionResolver $subscriptionResolver,
        private readonly WordCountService $wordCountService,
    ) {}

    public function getPlanForUser(User $user): Plan
    {
        return $this->subscriptionResolver->resolvePlan($user);
    }

    public function getReaderSettings(User $user): PlanReaderSettings
    {
        $plan = $this->getPlanForUser($user)->loadMissing('readerSettings');

        if ($plan->readerSettings) {
            return $plan->readerSettings;
        }

        return new PlanReaderSettings(array_merge(['plan_id' => $plan->id], PlanDefaults::for($plan->code)));
    }

    public function isFeatureEnabled(User $user, string $feature): bool
    {
        $column = self::FEATURE_FLAGS[$feature] ?? null;

        if (! $column) {
            return false;
        }

        $settings = $this->getReaderSettings($user);

        return $settings->is_active && (bool) $settings->{$column};
    }

    public function getMaxWords(User $user, string $feature): int
    {
        $column = self::WORD_LIMITS[$feature] ?? null;

        if (! $column) {
            return 0;
        }

        return (int) $this->getReaderSettings($user)->{$column};
    }

    public function canUseAiTts(User $user): bool
    {
        if (! $this->isFeatureEnabled($user, 'ai_tts')) {
            return false;
        }

        $limit = $this->getMonthlyTtsCharacterLimit($user);

        if ($limit === null) {
            return true;
        }

        return $this->getMonthlyTtsUsage($user) < $limit;
    }

    public function canUseBrowserTts(User $user): bool
    {
        return $this->isFeatureEnabled($user, 'browser_tts');
    }

    public function canUseShadowing(User $user): bool
    {
        return $this->isFeatureEnabled($user, 'shadowing');
    }

    public function getLimit(User $user, string $limit): ?int
    {
        $settings = $this->getReaderSettings($user);

        return match ($limit) {
            'ai_actions_daily_limit' => $settings->ai_actions_daily_limit,
            'ai_actions_monthly_limit' => $settings->ai_actions_monthly_limit,
            'ai_tts_monthly_characters' => $settings->ai_tts_monthly_characters,
            'vocabulary_entries_limit' => $settings->vocabulary_entries_limit,
            'private_books_limit' => $settings->private_books_limit,
            default => null,
        };
    }

    public function canImportBook(User $user): bool
    {
        if (! $this->isFeatureEnabled($user, 'import_private_books')) {
            return false;
        }

        $limit = $this->getLimit($user, 'private_books_limit');

        return $limit === null || $user->books()->count() < $limit;
    }

    public function canSelectVoice(User $user): bool
    {
        return $this->isFeatureEnabled($user, 'voice_selection');
    }

    public function getDailyAiLimit(User $user): int
    {
        return (int) $this->getReaderSettings($user)->ai_actions_daily_limit;
    }

    public function getMonthlyAiLimit(User $user): ?int
    {
        return $this->getReaderSettings($user)->ai_actions_monthly_limit;
    }

    public function getMonthlyTtsCharacterLimit(User $user): ?int
    {
        $value = $this->getReaderSettings($user)->ai_tts_monthly_characters;

        return $value === null ? null : (int) $value;
    }

    public function getMonthlyTtsUsage(User $user): int
    {
        return (int) AiUsageEvent::query()
            ->where('user_id', $user->id)
            ->where('operation_type', AiOperationType::Tts->value)
            ->where('provider_called', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('request_characters');
    }

    public function validateWordLimit(User $user, string $feature, string $text): array
    {
        $currentWords = $this->wordCountService->count($text);
        $maxWords = $this->getMaxWords($user, $feature);

        return [
            'allowed' => $maxWords <= 0 || $currentWords <= $maxWords,
            'feature' => $feature,
            'current_words' => $currentWords,
            'max_words' => $maxWords,
            'plan' => $this->getPlanForUser($user)->code,
            'upgrade_available' => ! $this->getPlanForUser($user)->isPro(),
        ];
    }

    public function getUserCapabilities(User $user): array
    {
        $plan = $this->getPlanForUser($user);
        $settings = $this->getReaderSettings($user);
        $monthlyTtsLimit = $this->getMonthlyTtsCharacterLimit($user);

        return [
            'plan' => $plan->code,
            'limits' => [
                'translation_max_words' => $settings->translation_max_words,
                'context_max_words' => $settings->context_max_words,
                'grammar_max_words' => $settings->grammar_max_words,
                'simplify_max_words' => $settings->simplify_max_words,
                'tts_max_words' => $settings->tts_max_words,
                'pronunciation_max_words' => $settings->pronunciation_max_words,
                'vocabulary_max_words' => $settings->vocabulary_max_words,
                'vocabulary_phrase_max_words' => $settings->vocabulary_max_words,
            ],
            'features' => [
                'translation' => $settings->translation_enabled,
                'context' => $settings->context_enabled,
                'grammar' => $settings->grammar_enabled,
                'simplify' => $settings->simplify_enabled,
                'browser_tts' => $settings->browser_tts_enabled,
                'ai_tts' => $settings->ai_tts_enabled,
                'pronunciation' => $settings->pronunciation_recording_enabled,
                'shadowing' => $settings->shadowing_enabled,
                'voice_selection' => $settings->voice_selection_enabled,
                'vocabulary' => $settings->vocabulary_enabled,
                'daily_goal' => $settings->daily_goal_enabled,
                'streak' => $settings->streak_enabled,
                'import_private_books' => $settings->import_private_books_enabled,
                'public_library' => $settings->public_library_enabled,
            ],
            'usage' => [
                'ai_actions_remaining_today' => max(0, $settings->ai_actions_daily_limit - $this->getDailyAiUsage($user)),
                'ai_actions_remaining_month' => $settings->ai_actions_monthly_limit === null
                    ? null
                    : max(0, $settings->ai_actions_monthly_limit - $this->getMonthlyAiUsage($user)),
                'tts_characters_remaining_month' => $monthlyTtsLimit === null
                    ? null
                    : max(0, $monthlyTtsLimit - $this->getMonthlyTtsUsage($user)),
            ],
        ];
    }

    private function getDailyAiUsage(User $user): int
    {
        return (int) AiUsageEvent::query()
            ->where('user_id', $user->id)
            ->where('provider_called', true)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    private function getMonthlyAiUsage(User $user): int
    {
        return (int) AiUsageEvent::query()
            ->where('user_id', $user->id)
            ->where('provider_called', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }
}
