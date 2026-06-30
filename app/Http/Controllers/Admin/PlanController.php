<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanReaderSettingsRequest;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->with(['aiLimits', 'readerSettings'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('admin.plans.index', ['plans' => $plans]);
    }

    public function edit(Plan $plan): View
    {
        $plan->loadMissing(['aiLimits', 'readerSettings']);

        return view('admin.plans.edit', ['plan' => $plan]);
    }

    public function update(UpdatePlanReaderSettingsRequest $request, Plan $plan): RedirectResponse
    {
        $plan->loadMissing(['aiLimits', 'readerSettings']);
        $oldValues = [
            'plan' => $plan->only(['name', 'description', 'is_active', 'is_default', 'price_amount', 'price_currency', 'billing_interval', 'position']),
            'ai_limits' => $plan->aiLimits?->toArray() ?? [],
            'reader_settings' => $plan->readerSettings?->toArray() ?? [],
        ];

        $validated = $request->validated();

        $plan->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
            'price_amount' => $validated['price_amount'] ?? null,
            'price_currency' => $validated['price_currency'] ? mb_strtoupper($validated['price_currency']) : null,
            'billing_interval' => $validated['billing_interval'] ?? null,
            'position' => $validated['position'] ?? 0,
        ])->save();

        if ($request->boolean('is_default')) {
            Plan::whereKeyNot($plan->id)->update(['is_default' => false]);
        }

        $plan->aiLimits()->updateOrCreate([], [
            'translations_per_day' => $validated['translations_per_day'] ?? null,
            'translations_per_month' => $validated['translations_per_month'] ?? null,
            'explanations_per_day' => $validated['explanations_per_day'] ?? null,
            'explanations_per_month' => $validated['explanations_per_month'] ?? null,
            'context_explanations_per_day' => $validated['context_explanations_per_day'] ?? null,
            'context_explanations_per_month' => $validated['context_explanations_per_month'] ?? null,
            'grammar_explanations_per_day' => $validated['grammar_explanations_per_day'] ?? null,
            'grammar_explanations_per_month' => $validated['grammar_explanations_per_month'] ?? null,
            'simplifications_per_day' => $validated['simplifications_per_day'] ?? null,
            'simplifications_per_month' => $validated['simplifications_per_month'] ?? null,
            'tts_minutes_per_day' => $validated['tts_minutes_per_day'] ?? null,
            'tts_minutes_per_month' => $validated['tts_minutes_per_month'] ?? null,
            'max_translation_characters' => $validated['max_translation_characters'] ?? 500,
            'max_explanation_selected_characters' => $validated['max_explanation_selected_characters'] ?? 255,
            'max_explanation_context_characters' => $validated['max_explanation_context_characters'] ?? 1000,
            'max_context_explanation_characters' => $validated['max_context_explanation_characters'] ?? 1000,
            'max_grammar_explanation_characters' => $validated['max_grammar_explanation_characters'] ?? 1500,
            'max_simplification_characters' => $validated['max_simplification_characters'] ?? 4000,
            'max_tts_characters_per_request' => $validated['max_tts_characters_per_request'] ?? 500,
            'requests_per_minute' => $validated['requests_per_minute'] ?? 10,
            'concurrent_tts_requests' => $validated['concurrent_tts_requests'] ?? 1,
            'ai_translation_enabled' => $request->boolean('ai_translation_enabled'),
            'ai_explanation_enabled' => $request->boolean('ai_explanation_enabled'),
            'ai_context_explanation_enabled' => $request->boolean('ai_context_explanation_enabled'),
            'ai_grammar_explanation_enabled' => $request->boolean('ai_grammar_explanation_enabled'),
            'ai_simplification_enabled' => $request->boolean('ai_simplification_enabled'),
            'ai_tts_enabled' => $request->boolean('ai_tts_enabled'),
            'browser_tts_enabled' => $request->boolean('browser_tts_enabled'),
            'premium_books_enabled' => $request->boolean('premium_books_enabled'),
            'shadowing_enabled' => $request->boolean('shadowing_enabled'),
            'private_books_limit' => $validated['private_books_limit'] ?? null,
        ]);

        $plan->readerSettings()->updateOrCreate([], [
            'translation_max_words' => $validated['translation_max_words'],
            'context_max_words' => $validated['context_max_words'],
            'grammar_max_words' => $validated['grammar_max_words'],
            'simplify_max_words' => $validated['simplify_max_words'],
            'tts_max_words' => $validated['tts_max_words'],
            'pronunciation_max_words' => $validated['pronunciation_max_words'],
            'vocabulary_max_words' => $validated['vocabulary_max_words'],
            'ai_actions_daily_limit' => $validated['ai_actions_daily_limit'],
            'ai_tts_monthly_characters' => $validated['ai_tts_monthly_characters'] ?? null,
            'ai_tts_enabled' => $request->boolean('reader_ai_tts_enabled'),
            'browser_tts_enabled' => $request->boolean('reader_browser_tts_enabled'),
            'pronunciation_recording_enabled' => $request->boolean('pronunciation_recording_enabled'),
            'shadowing_enabled' => $request->boolean('reader_shadowing_enabled'),
            'voice_selection_enabled' => $request->boolean('voice_selection_enabled'),
            'context_enabled' => $request->boolean('context_enabled'),
            'grammar_enabled' => $request->boolean('grammar_enabled'),
            'simplify_enabled' => $request->boolean('simplify_enabled'),
            'translation_enabled' => $request->boolean('translation_enabled'),
            'vocabulary_enabled' => $request->boolean('vocabulary_enabled'),
            'is_active' => $request->boolean('reader_is_active'),
        ]);

        $plan->refresh()->load(['aiLimits', 'readerSettings']);
        $newValues = [
            'plan' => $plan->only(['name', 'description', 'is_active', 'is_default', 'price_amount', 'price_currency', 'billing_interval', 'position']),
            'ai_limits' => $plan->aiLimits?->toArray() ?? [],
            'reader_settings' => $plan->readerSettings?->toArray() ?? [],
        ];

        AdminAuditLog::create([
            'admin_id' => $request->user()?->id,
            'action' => 'plan.reader_settings.updated',
            'entity_type' => Plan::class,
            'entity_id' => $plan->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        Cache::forget('plan-reader-settings:'.$plan->id);

        return redirect()
            ->route('admin.plans.edit', $plan)
            ->with('status', 'Plan settings updated.');
    }
}
