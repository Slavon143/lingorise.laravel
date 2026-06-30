<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserAiLimitOverride;
use App\Models\UserSubscription;
use App\Services\Intelligence\Subscription\SubscriptionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $users = User::query()
            ->select(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->query('q'));
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->query('admin') === 'yes', fn ($query) => $query->where('is_admin', true))
            ->when($request->query('admin') === 'no', fn ($query) => $query->where('is_admin', false))
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', fn ($query) => $query->orderBy('id', $direction))
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(User $user, SubscriptionResolver $resolver): View
    {
        $user->loadCount('books');
        $user->load('activeSubscription.plan');
        $plans = Plan::where('is_active', true)->orderBy('position')->get(['id', 'name', 'code']);
        $subscription = $user->activeSubscription;
        $overrides = UserAiLimitOverride::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->first();

        return view('admin.users.show', [
            'managedUser' => $user,
            'plans' => $plans,
            'subscription' => $subscription,
            'overrides' => $overrides,
            'effectivePlan' => $resolver->resolvePlan($user),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['managedUser' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $oldValues = $user->only(['name', 'email']);
        $validated = $request->validated();

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        $newValues = $user->only(['name', 'email']);

        if ($oldValues !== $newValues) {
            $this->audit($request, 'user.updated', $user, $oldValues, $newValues);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User updated successfully.');
    }

    public function promote(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('status', 'User is already an administrator.');
        }

        $oldValues = ['is_admin' => false];
        $user->forceFill(['is_admin' => true])->save();
        $this->audit($request, 'user.promoted_to_admin', $user, $oldValues, ['is_admin' => true]);

        return back()->with('status', 'Administrator rights granted.');
    }

    public function changePlan(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'ends_at' => 'nullable|date|after:now',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'source' => SubscriptionSource::Manual->value,
        ]);

        $this->audit($request, 'subscription.changed', $user, [], [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);

        return back()->with('status', "Subscription changed to {$plan->name}.");
    }

    public function cancelSubscription(Request $request, User $user): RedirectResponse
    {
        $subscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->latest()
            ->first();

        if (! $subscription) {
            return back()->withErrors(['subscription' => 'User has no active subscription.']);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        $this->audit($request, 'subscription.cancelled', $user, [], ['subscription_id' => $subscription->id]);

        return back()->with('status', 'Subscription cancelled.');
    }

    public function storeOverride(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'translations_per_day' => 'nullable|integer|min:0',
            'translations_per_month' => 'nullable|integer|min:0',
            'explanations_per_day' => 'nullable|integer|min:0',
            'explanations_per_month' => 'nullable|integer|min:0',
            'tts_minutes_per_day' => 'nullable|integer|min:0',
            'tts_minutes_per_month' => 'nullable|integer|min:0',
            'max_translation_characters' => 'nullable|integer|min:0',
            'max_explanation_context_characters' => 'nullable|integer|min:0',
            'max_tts_characters_per_request' => 'nullable|integer|min:0',
            'ai_translation_enabled' => 'nullable|boolean',
            'ai_explanation_enabled' => 'nullable|boolean',
            'ai_tts_enabled' => 'nullable|boolean',
            'browser_tts_enabled' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'reason' => 'nullable|string|max:500',
        ]);

        // Deactivate existing active overrides
        UserAiLimitOverride::where('user_id', $user->id)
            ->whereNull('ends_at')
            ->orWhere('ends_at', '>', now())
            ->update(['ends_at' => now()]);

        UserAiLimitOverride::create(array_merge(
            collect($validated)->except(['starts_at', 'ends_at', 'reason'])->toArray(),
            [
                'user_id' => $user->id,
                'starts_at' => $validated['starts_at'] ?? now(),
                'ends_at' => $validated['ends_at'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'created_by' => $request->user()->id,
            ],
        ));

        $this->audit($request, 'override.created', $user, [], $validated);

        return back()->with('status', 'Override created.');
    }

    public function removeOverride(Request $request, User $user, UserAiLimitOverride $override): RedirectResponse
    {
        if ($override->user_id !== $user->id) {
            abort(404);
        }

        $override->update(['ends_at' => now()]);

        $this->audit($request, 'override.removed', $user, [], ['override_id' => $override->id]);

        return back()->with('status', 'Override removed.');
    }

    public function demote(Request $request, User $user): RedirectResponse
    {
        if (! $user->isAdmin()) {
            return back()->with('status', 'User is not an administrator.');
        }

        if (User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors([
                'admin' => 'You cannot remove the last administrator.',
            ]);
        }

        $oldValues = ['is_admin' => true];
        $user->forceFill(['is_admin' => false])->save();
        $this->audit($request, 'user.demoted_from_admin', $user, $oldValues, ['is_admin' => false]);

        return back()->with('status', 'Administrator rights removed.');
    }

    private function audit(Request $request, string $action, User $entity, array $oldValues, array $newValues): void
    {
        AdminAuditLog::create([
            'admin_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
