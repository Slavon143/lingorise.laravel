<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use App\Models\PlanAiLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::with('aiLimits')->orderBy('position')->get();
        return view('admin.plans.index', ['plans' => $plans]);
    }

    public function edit(Plan $plan): View
    {
        $plan->load('aiLimits');
        return view('admin.plans.edit', ['plan' => $plan]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer|min:0',
            'price_amount' => 'nullable|numeric|min:0',
            'price_currency' => 'nullable|string|size:3',
            'billing_interval' => 'nullable|in:month,year',
            'translations_per_day' => 'nullable|integer|min:0',
            'translations_per_month' => 'nullable|integer|min:0',
            'explanations_per_day' => 'nullable|integer|min:0',
            'explanations_per_month' => 'nullable|integer|min:0',
            'tts_minutes_per_day' => 'nullable|integer|min:0',
            'tts_minutes_per_month' => 'nullable|integer|min:0',
            'max_translation_characters' => 'nullable|integer|min:0',
            'max_explanation_context_characters' => 'nullable|integer|min:0',
            'max_tts_characters_per_request' => 'nullable|integer|min:0',
            'requests_per_minute' => 'nullable|integer|min:0',
            'concurrent_tts_requests' => 'nullable|integer|min:0',
            'ai_translation_enabled' => 'boolean',
            'ai_explanation_enabled' => 'boolean',
            'ai_tts_enabled' => 'boolean',
            'browser_tts_enabled' => 'boolean',
            'premium_books_enabled' => 'boolean',
            'private_books_limit' => 'nullable|integer|min:-1',
        ]);

        $oldPlan = $plan->only(['name', 'description', 'is_active', 'is_default', 'position', 'price_amount', 'price_currency', 'billing_interval']);

        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'is_default' => $validated['is_default'] ?? false,
            'position' => $validated['position'] ?? 0,
            'price_amount' => $validated['price_amount'] ?? null,
            'price_currency' => $validated['price_currency'] ?? null,
            'billing_interval' => $validated['billing_interval'] ?? null,
        ]);

        if ($validated['is_default'] ?? false) {
            Plan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        $limitFields = [
            'translations_per_day', 'translations_per_month',
            'explanations_per_day', 'explanations_per_month',
            'tts_minutes_per_day', 'tts_minutes_per_month',
            'max_translation_characters', 'max_explanation_context_characters',
            'max_tts_characters_per_request', 'requests_per_minute',
            'concurrent_tts_requests', 'ai_translation_enabled',
            'ai_explanation_enabled', 'ai_tts_enabled', 'browser_tts_enabled',
            'premium_books_enabled', 'private_books_limit',
        ];

        $plan->aiLimits()->updateOrCreate(
            ['plan_id' => $plan->id],
            collect($limitFields)->mapWithKeys(fn ($f) => [$f => $validated[$f] ?? null])->toArray(),
        );

        $this->audit($request, 'plan.updated', $plan, $oldPlan, $plan->only(array_keys($oldPlan)));

        return redirect()->route('admin.plans.edit', $plan)->with('status', 'Plan updated successfully.');
    }

    private function audit(Request $request, string $action, Plan $entity, array $oldValues, array $newValues): void
    {
        AdminAuditLog::create([
            'admin_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => Plan::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
