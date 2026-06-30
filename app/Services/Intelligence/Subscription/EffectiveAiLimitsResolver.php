<?php

namespace App\Services\Intelligence\Subscription;

use App\Models\Plan;
use App\Models\PlanAiLimit;
use App\Models\User;
use App\Models\UserAiLimitOverride;
use App\Services\Intelligence\Budget\AiBudgetGuard;

class EffectiveAiLimitsResolver
{
    public function __construct(
        private readonly SubscriptionResolver $subscriptionResolver,
        private readonly AiBudgetGuard $budget,
    ) {}

    public function resolve(User $user): EffectiveLimits
    {
        $plan = $this->subscriptionResolver->resolvePlan($user);
        $limits = $plan->aiLimits ?? new PlanAiLimit(['plan_id' => $plan->id]);
        $override = UserAiLimitOverride::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->first();

        return new EffectiveLimits($plan, $limits, $override, $this->budget);
    }
}
