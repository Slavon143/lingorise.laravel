<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Services\Intelligence\Subscription\SubscriptionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __construct(
        private readonly SubscriptionResolver $subscriptionResolver,
    ) {}

    public function index(): View
    {
        $plans = Plan::where('is_active', true)
            ->with('aiLimits')
            ->orderBy('position')
            ->get();

        return view('pricing.index', [
            'plans' => $plans,
            'currentPlan' => $this->subscriptionResolver->resolvePlan(request()->user()),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $planId = $request->integer('plan_id');

        if ($planId <= 0) {
            $plan = Plan::where('code', 'premium')->where('is_active', true)->firstOrFail();
        } else {
            $plan = Plan::findOrFail($planId);
        }

        if (! $plan->is_active) {
            return back()->withErrors(['plan' => 'This plan is not available.']);
        }

        $request->user()->subscription()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now(),
            'source' => SubscriptionSource::Manual->value,
        ]);

        return redirect()->route('pricing.index')
            ->with('status', "Welcome to {$plan->name}!");
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();

        $active = $user->subscription()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->latest()
            ->first();

        if ($active) {
            $active->update([
                'status' => SubscriptionStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);
        }

        return redirect()->route('pricing.index')
            ->with('status', 'Your subscription has been cancelled.');
    }
}
