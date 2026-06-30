<?php

namespace App\Services\Intelligence\Budget;

use App\Enums\AiOperationType;
use App\Models\AiUsageEvent;
use Illuminate\Support\Facades\Cache;

class AiBudgetGuard
{
    public function isAllowed(AiOperationType $operation): bool
    {
        if (! $this->isAiEnabled()) {
            return false;
        }

        if (! match ($operation) {
            AiOperationType::Translation => $this->isTranslationEnabled(),
            AiOperationType::Explanation => $this->isExplanationEnabled(),
            AiOperationType::Tts => $this->isTtsEnabled(),
            AiOperationType::ContextExplanation => $this->isExplanationEnabled(),
            AiOperationType::GrammarExplanation => $this->isExplanationEnabled(),
            AiOperationType::Simplification => $this->isExplanationEnabled(),
            default => $this->isAiEnabled(),
        }) {
            return false;
        }

        if ($this->isHardStopReached()) {
            return false;
        }

        return true;
    }

    public function isCacheHitAllowed(AiOperationType $operation): bool
    {
        if (! $this->isAiEnabled()) {
            return false;
        }

        return match ($operation) {
            AiOperationType::Translation => $this->isTranslationEnabled(),
            AiOperationType::Explanation => $this->isExplanationEnabled(),
            AiOperationType::Tts => $this->isTtsEnabled(),
            AiOperationType::ContextExplanation => $this->isExplanationEnabled(),
            AiOperationType::GrammarExplanation => $this->isExplanationEnabled(),
            AiOperationType::Simplification => $this->isExplanationEnabled(),
            default => $this->isAiEnabled(),
        };
    }

    public function isWarningReached(): bool
    {
        $monthlyCost = $this->getMonthlyCost();
        $budget = $this->getMonthlyBudget();

        if ($budget <= 0) {
            return false;
        }

        $threshold = (float) config('ai_pricing.warning_threshold_percent', 80);

        return ($monthlyCost / $budget) * 100 >= $threshold;
    }

    public function isHardStopReached(): bool
    {
        $monthlyCost = $this->getMonthlyCost();
        $budget = $this->getMonthlyBudget();

        if ($budget <= 0) {
            return false;
        }

        $threshold = (float) config('ai_pricing.hard_stop_threshold_percent', 100);

        return ($monthlyCost / $budget) * 100 >= $threshold;
    }

    public function getMonthlyCost(): float
    {
        $cacheKey = 'ai:monthly-cost:' . now()->format('Y-m');

        return Cache::remember($cacheKey, 300, function (): float {
            return (float) AiUsageEvent::where('created_at', '>=', now()->startOfMonth())
                ->where('provider_called', true)
                ->sum('estimated_cost_usd');
        });
    }

    public function getMonthlyBudget(): float
    {
        return (float) config('ai_pricing.monthly_budget_usd', 0);
    }

    public function isAiEnabled(): bool
    {
        return (bool) config('ai_pricing.ai_enabled', true);
    }

    public function isTranslationEnabled(): bool
    {
        return (bool) config('ai_pricing.translation_enabled', true);
    }

    public function isExplanationEnabled(): bool
    {
        return (bool) config('ai_pricing.explanation_enabled', true);
    }

    public function isTtsEnabled(): bool
    {
        return (bool) config('ai_pricing.tts_enabled', true);
    }

    public function isBrowserTtsFallbackEnabled(): bool
    {
        return (bool) config('ai_pricing.browser_tts_fallback_enabled', true);
    }
}
