<?php

namespace App\Services\Intelligence\Subscription;

use App\Models\Plan;
use App\Models\PlanAiLimit;
use App\Models\UserAiLimitOverride;
use App\Services\Intelligence\Budget\AiBudgetGuard;

class EffectiveLimits
{
    public function __construct(
        public readonly Plan $plan,
        public readonly PlanAiLimit $planLimits,
        public readonly ?UserAiLimitOverride $override,
        private readonly AiBudgetGuard $budget,
    ) {}

    private function value(string $field): mixed
    {
        if ($this->override && $this->override->{$field} !== null) {
            return $this->override->{$field};
        }

        return $this->planLimits->{$field} ?? null;
    }

    public function translationsPerDay(): ?int { return $this->value('translations_per_day'); }
    public function translationsPerMonth(): ?int { return $this->value('translations_per_month'); }
    public function explanationsPerDay(): ?int { return $this->value('explanations_per_day'); }
    public function explanationsPerMonth(): ?int { return $this->value('explanations_per_month'); }
    public function contextExplanationsPerDay(): ?int { return $this->value('context_explanations_per_day'); }
    public function contextExplanationsPerMonth(): ?int { return $this->value('context_explanations_per_month'); }
    public function grammarExplanationsPerDay(): ?int { return $this->value('grammar_explanations_per_day'); }
    public function grammarExplanationsPerMonth(): ?int { return $this->value('grammar_explanations_per_month'); }
    public function simplificationsPerDay(): ?int { return $this->value('simplifications_per_day'); }
    public function simplificationsPerMonth(): ?int { return $this->value('simplifications_per_month'); }
    public function ttsMinutesPerDay(): ?int { return $this->value('tts_minutes_per_day'); }
    public function ttsMinutesPerMonth(): ?int { return $this->value('tts_minutes_per_month'); }
    public function maxTranslationCharacters(): int { return (int) $this->value('max_translation_characters'); }
    public function maxExplanationContextCharacters(): int { return (int) $this->value('max_explanation_context_characters'); }
    public function maxTtsCharactersPerRequest(): int { return (int) $this->value('max_tts_characters_per_request'); }
    public function requestsPerMinute(): int { return (int) $this->value('requests_per_minute'); }
    public function concurrentTtsRequests(): int { return (int) $this->value('concurrent_tts_requests'); }

    public function aiTranslationEnabled(): bool
    {
        if (! $this->budget->isTranslationEnabled()) return false;
        $enabled = $this->value('ai_translation_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function aiExplanationEnabled(): bool
    {
        if (! $this->budget->isExplanationEnabled()) return false;
        $enabled = $this->value('ai_explanation_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function aiTtsEnabled(): bool
    {
        if (! $this->budget->isTtsEnabled()) return false;
        $enabled = $this->value('ai_tts_enabled');
        if ($enabled === null) return false;
        return (bool) $enabled;
    }

    public function browserTtsEnabled(): bool
    {
        $enabled = $this->value('browser_tts_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function premiumBooksEnabled(): bool
    {
        $enabled = $this->value('premium_books_enabled');
        if ($enabled === null) return false;
        return (bool) $enabled;
    }

    public function privateBooksLimit(): ?int { return $this->value('private_books_limit'); }

    public function contextExplanationEnabled(): bool
    {
        if (! config('intelligence.context_explanation_enabled', true)) return false;
        $enabled = $this->value('ai_context_explanation_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function grammarExplanationEnabled(): bool
    {
        if (! config('intelligence.grammar_explanation_enabled', true)) return false;
        $enabled = $this->value('ai_grammar_explanation_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function simplificationEnabled(): bool
    {
        if (! config('intelligence.simplification_enabled', true)) return false;
        $enabled = $this->value('ai_simplification_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }

    public function shadowingEnabled(): bool
    {
        if (! config('intelligence.shadowing_enabled', true)) return false;
        $enabled = $this->value('shadowing_enabled');
        if ($enabled === null) return true;
        return (bool) $enabled;
    }
}
