<?php

namespace App\Services\Intelligence\Subscription;

use App\Enums\AiOperationType;
use App\Enums\AiQuotaError;
use App\Models\User;
use App\Services\Intelligence\Budget\AiBudgetGuard;

class AiQuotaGuard
{
    public function __construct(
        private readonly EffectiveAiLimitsResolver $limitsResolver,
        private readonly UserQuotaService $quota,
        private readonly SubscriptionResolver $subscriptionResolver,
        private readonly AiBudgetGuard $budget,
    ) {}

    public function canTranslate(User $user): bool
    {
        try {
            $this->assertTranslationAllowed($user);
            return true;
        } catch (AiQuotaExceededException) {
            return false;
        }
    }

    public function canExplain(User $user): bool
    {
        try {
            $this->assertExplanationAllowed($user);
            return true;
        } catch (AiQuotaExceededException) {
            return false;
        }
    }

    public function canUseTts(User $user): bool
    {
        try {
            $this->assertTtsAllowed($user);
            return true;
        } catch (AiQuotaExceededException) {
            return false;
        }
    }

    public function assertTranslationAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->aiTranslationEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::TranslationDisabled,
                'AI translation is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $daily = $limits->translationsPerDay();
        $monthly = $limits->translationsPerMonth();

        if ($daily !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Translation, $this->quota->dailyPeriod($timezone));
            if ($used >= $daily) {
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyTranslationLimitExceeded,
                    'Your daily translation limit has been reached.',
                    now($timezone)->endOfDay()->timezone('UTC'),
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Translation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyTranslationLimitExceeded,
                    'Your monthly translation limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }

    public function assertExplanationAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->aiExplanationEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::ExplanationDisabled,
                'AI explanation is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $daily = $limits->explanationsPerDay();
        $monthly = $limits->explanationsPerMonth();

        if ($daily !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Explanation, $this->quota->dailyPeriod($timezone));
            if ($used >= $daily) {
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyExplanationLimitExceeded,
                    'Your daily explanation limit has been reached.',
                    now($timezone)->endOfDay()->timezone('UTC'),
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Explanation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyExplanationLimitExceeded,
                    'Your monthly explanation limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }

    public function assertTtsAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->aiTtsEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::TtsDisabled,
                'AI voice is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $monthly = $limits->ttsMinutesPerMonth();

        if ($monthly !== null) {
            $used = $this->quota->ttsMinutesUsed($user->id, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyTtsLimitExceeded,
                    'Your monthly AI voice limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }

    public function assertConcurrentTtsAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);
        $max = $limits->concurrentTtsRequests();

        if ($max <= 0) {
            throw new AiQuotaExceededException(
                AiQuotaError::TtsDisabled,
                'AI voice is not available on your current plan.',
            );
        }
    }

    public function assertContextExplanationAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->contextExplanationEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::ContextExplanationDisabled,
                'Context explanation is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $daily = $limits->contextExplanationsPerDay();
        $monthly = $limits->contextExplanationsPerMonth();

        if ($daily !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::ContextExplanation, $this->quota->dailyPeriod($timezone));
            if ($used >= $daily) {
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyContextExplanationLimitExceeded,
                    'Your daily context explanation limit has been reached.',
                    now($timezone)->endOfDay()->timezone('UTC'),
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::ContextExplanation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyContextExplanationLimitExceeded,
                    'Your monthly context explanation limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }

    public function assertGrammarExplanationAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->grammarExplanationEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::GrammarExplanationDisabled,
                'Grammar explanation is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $daily = $limits->grammarExplanationsPerDay();
        $monthly = $limits->grammarExplanationsPerMonth();

        if ($daily !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::GrammarExplanation, $this->quota->dailyPeriod($timezone));
            if ($used >= $daily) {
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyGrammarExplanationLimitExceeded,
                    'Your daily grammar explanation limit has been reached.',
                    now($timezone)->endOfDay()->timezone('UTC'),
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::GrammarExplanation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyGrammarExplanationLimitExceeded,
                    'Your monthly grammar explanation limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }

    public function assertSimplificationAllowed(User $user): void
    {
        $limits = $this->limitsResolver->resolve($user);

        if (! $limits->simplificationEnabled()) {
            throw new AiQuotaExceededException(
                AiQuotaError::SimplificationDisabled,
                'Simplification is not available on your current plan.',
            );
        }

        $timezone = config('app.timezone');
        $daily = $limits->simplificationsPerDay();
        $monthly = $limits->simplificationsPerMonth();

        if ($daily !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Simplification, $this->quota->dailyPeriod($timezone));
            if ($used >= $daily) {
                throw new AiQuotaExceededException(
                    AiQuotaError::DailySimplificationLimitExceeded,
                    'Your daily simplification limit has been reached.',
                    now($timezone)->endOfDay()->timezone('UTC'),
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Simplification, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlySimplificationLimitExceeded,
                    'Your monthly simplification limit has been reached.',
                    now($timezone)->endOfMonth()->timezone('UTC'),
                );
            }
        }
    }
}
