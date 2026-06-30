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

        return new PlanReaderSettings(array_merge(
            ['plan_id' => $plan->id],
            $this->defaultSettingsForPlan($plan),
        ));
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

    public function canSelectVoice(User $user): bool
    {
        return $this->isFeatureEnabled($user, 'voice_selection');
    }

    public function getDailyAiLimit(User $user): int
    {
        return (int) $this->getReaderSettings($user)->ai_actions_daily_limit;
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
            ],
            'usage' => [
                'ai_actions_remaining_today' => max(0, $settings->ai_actions_daily_limit - $this->getDailyAiUsage($user)),
                'tts_characters_remaining_this_month' => $monthlyTtsLimit === null
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

    private function defaultSettingsForPlan(Plan $plan): array
    {
        if ($plan->isPremium()) {
            return [
                'translation_max_words' => 30,
                'context_max_words' => 20,
                'grammar_max_words' => 30,
                'simplify_max_words' => 30,
                'tts_max_words' => 30,
                'pronunciation_max_words' => 25,
                'vocabulary_max_words' => 20,
                'ai_actions_daily_limit' => 100,
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
                'is_active' => true,
            ];
        }

        if ($plan->isPro() || $plan->isAdmin()) {
            return [
                'translation_max_words' => 50,
                'context_max_words' => 30,
                'grammar_max_words' => 50,
                'simplify_max_words' => 40,
                'tts_max_words' => 50,
                'pronunciation_max_words' => 30,
                'vocabulary_max_words' => 25,
                'ai_actions_daily_limit' => 300,
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
                'is_active' => true,
            ];
        }

        return [
            'translation_max_words' => 10,
            'context_max_words' => 6,
            'grammar_max_words' => 10,
            'simplify_max_words' => 10,
            'tts_max_words' => 10,
            'pronunciation_max_words' => 10,
            'vocabulary_max_words' => 10,
            'ai_actions_daily_limit' => 10,
            'ai_tts_monthly_characters' => null,
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
            'is_active' => true,
        ];
    }
}
