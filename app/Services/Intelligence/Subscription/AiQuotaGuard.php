<?php

namespace App\Services\Intelligence\Subscription;

use App\Enums\AiOperationType;
use App\Enums\AiQuotaError;
use App\Models\User;
use App\Services\Intelligence\Budget\AiBudgetGuard;
use RuntimeException;

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
                $resetsAt = now($timezone)->endOfDay()->timezone('UTC');
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyTranslationLimitExceeded,
                    'Your daily translation limit has been reached.',
                    $resetsAt,
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Translation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                $resetsAt = now($timezone)->endOfMonth()->timezone('UTC');
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyTranslationLimitExceeded,
                    'Your monthly translation limit has been reached.',
                    $resetsAt,
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
                $resetsAt = now($timezone)->endOfDay()->timezone('UTC');
                throw new AiQuotaExceededException(
                    AiQuotaError::DailyExplanationLimitExceeded,
                    'Your daily explanation limit has been reached.',
                    $resetsAt,
                );
            }
        }

        if ($monthly !== null) {
            $used = $this->quota->countProviderCalls($user->id, AiOperationType::Explanation, $this->quota->monthlyPeriod($timezone));
            if ($used >= $monthly) {
                $resetsAt = now($timezone)->endOfMonth()->timezone('UTC');
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyExplanationLimitExceeded,
                    'Your monthly explanation limit has been reached.',
                    $resetsAt,
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
                $resetsAt = now($timezone)->endOfMonth()->timezone('UTC');
                throw new AiQuotaExceededException(
                    AiQuotaError::MonthlyTtsLimitExceeded,
                    'Your monthly AI voice limit has been reached.',
                    $resetsAt,
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
}
