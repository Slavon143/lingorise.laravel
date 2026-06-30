<?php

namespace App\Http\Controllers;

use App\Enums\PlanCode;
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
            ->whereIn('code', [
                PlanCode::Free->value,
                PlanCode::Premium->value,
                PlanCode::Pro->value,
            ])
            ->with(['aiLimits', 'readerSettings'])
            ->orderBy('position')
            ->get();

        $matrixRows = [
            'Reader' => [
                ['label' => 'Translation max words', 'key' => 'translation_max_words', 'suffix' => ' words'],
                ['label' => 'Context max words', 'key' => 'context_max_words', 'suffix' => ' words'],
                ['label' => 'Grammar max words', 'key' => 'grammar_max_words', 'suffix' => ' words'],
                ['label' => 'Simplify max words', 'key' => 'simplify_max_words', 'suffix' => ' words'],
                ['label' => 'Listen max words', 'key' => 'tts_max_words', 'suffix' => ' words'],
                ['label' => 'Pronunciation max words', 'key' => 'pronunciation_max_words', 'suffix' => ' words'],
            ],
            'AI' => [
                ['label' => 'AI actions per day', 'key' => 'ai_actions_daily_limit'],
                ['label' => 'AI actions per month', 'key' => 'ai_actions_monthly_limit'],
                ['label' => 'Natural AI voice', 'key' => 'ai_tts_enabled', 'boolean' => true],
                ['label' => 'Monthly AI TTS characters', 'key' => 'ai_tts_monthly_characters', 'suffix' => ' characters/month'],
                ['label' => 'Browser voice', 'key' => 'browser_tts_enabled', 'boolean' => true],
                ['label' => 'Voice selection', 'key' => 'voice_selection_enabled', 'boolean' => true],
                ['label' => 'Shadowing', 'key' => 'shadowing_enabled', 'boolean' => true],
            ],
            'Learning' => [
                ['label' => 'Vocabulary entries', 'key' => 'vocabulary_entries_limit'],
                ['label' => 'Private books', 'key' => 'private_books_limit'],
                ['label' => 'Daily goal', 'key' => 'daily_goal_enabled', 'boolean' => true],
                ['label' => 'Reading streak', 'key' => 'streak_enabled', 'boolean' => true],
                ['label' => 'Practice pronunciation', 'key' => 'pronunciation_recording_enabled', 'boolean' => true],
                ['label' => 'Public library', 'key' => 'public_library_enabled', 'boolean' => true],
            ],
        ];

        return view('pricing.index', [
            'plans' => $plans,
            'currentPlan' => $this->subscriptionResolver->resolvePlan(request()->user()),
            'matrixRows' => $matrixRows,
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $planId = $request->integer('plan_id');

        if ($planId <= 0) {
            $plan = Plan::where('code', 'premium')->where('is_active', true)->firstOrFail();
        } else {
            $plan = Plan::whereIn('code', [
                    PlanCode::Free->value,
                    PlanCode::Premium->value,
                    PlanCode::Pro->value,
                ])
                ->findOrFail($planId);
        }

        if (! $plan->is_active || $plan->isAdmin()) {
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
