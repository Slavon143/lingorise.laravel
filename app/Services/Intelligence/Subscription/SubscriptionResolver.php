<?php

namespace App\Services\Intelligence\Subscription;

use App\Enums\PlanCode;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;

class SubscriptionResolver
{
    public function resolvePlan(User $user): Plan
    {
        if ($user->isAdmin()) {
            return $this->adminPlan();
        }

        $subscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($subscription) {
            return $subscription->plan;
        }

        return $this->defaultPlan();
    }

    public function resolveSubscription(User $user): ?UserSubscription
    {
        if ($user->isAdmin()) {
            return null;
        }

        return UserSubscription::where('user_id', $user->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    private function adminPlan(): Plan
    {
        return Plan::where('code', PlanCode::Admin->value)->firstOrFail();
    }

    private function defaultPlan(): Plan
    {
        return Plan::where('is_default', true)->firstOrFail();
    }
}
