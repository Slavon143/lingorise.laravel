@extends('admin.layouts.app')

@section('title', 'User #'.$managedUser->id)
@section('eyebrow', 'Account profile')

@section('content')
    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="admin-kicker">Identity</span>
                    <h2>{{ $managedUser->name }}</h2>
                </div>
                <a class="admin-link-button" href="{{ route('admin.users.edit', $managedUser) }}">Edit user</a>
            </div>

            <dl class="admin-details">
                <div><dt>ID</dt><dd>#{{ $managedUser->id }}</dd></div>
                <div><dt>Name</dt><dd>{{ $managedUser->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $managedUser->email }}</dd></div>
                <div><dt>Admin status</dt><dd><span class="admin-badge {{ $managedUser->is_admin ? 'is-admin' : '' }}">{{ $managedUser->is_admin ? 'Admin' : 'User' }}</span></dd></div>
                <div><dt>Plan</dt><dd><strong>{{ $effectivePlan->name }}</strong> @if($subscription && $subscription->ends_at) · expires {{ $subscription->ends_at->format('M d, Y') }}@endif</dd></div>
                <div><dt>Books</dt><dd>{{ number_format($managedUser->books_count) }}</dd></div>
                <div><dt>Registered at</dt><dd>{{ $managedUser->created_at?->format('M d, Y H:i') }}</dd></div>
                <div><dt>Updated at</dt><dd>{{ $managedUser->updated_at?->format('M d, Y H:i') }}</dd></div>
            </dl>
        </article>

        <aside class="admin-panel admin-action-panel">
            <span class="admin-kicker">Access control</span>
            <h2>Admin rights</h2>
            <p>Promotion and demotion are separate protected actions and are written to the audit log.</p>

            @if($managedUser->is_admin)
                <form method="POST" action="{{ route('admin.users.demote', $managedUser) }}" onsubmit="return confirm('Remove administrator rights from this user?')">
                    @csrf
                    <button class="admin-danger-button" type="submit">Remove admin rights</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.promote', $managedUser) }}" onsubmit="return confirm('Grant administrator rights to this user?')">
                    @csrf
                    <button class="admin-primary-button" type="submit">Make administrator</button>
                </form>
            @endif

            <a class="admin-muted-link" href="{{ route('admin.users.index') }}">← Back to users</a>
        </aside>
    </section>

    @unless($managedUser->is_admin)
    <section class="admin-panel" style="margin-top: 1.5rem">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Plan</span>
                <h2>Change subscription</h2>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.change-plan', $managedUser) }}" style="display:flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            <label>
                <span>Plan</span>
                <select name="plan_id" required>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" @selected($subscription && $subscription->plan_id === $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Expires at (optional)</span>
                <input type="date" name="ends_at">
            </label>
            <button type="submit" class="admin-button">Change plan</button>
        </form>

        @if($subscription && in_array($subscription->status, ['active', 'trialing']))
            <form method="POST" action="{{ route('admin.users.cancel-subscription', $managedUser) }}" style="margin-top: 0.75rem" onsubmit="return confirm('Cancel this subscription?')">
                @csrf
                <button type="submit" class="admin-danger-button">Cancel subscription</button>
            </form>
        @endif
    </section>

    <section class="admin-panel" style="margin-top: 1.5rem">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Limits</span>
                <h2>Active override</h2>
            </div>
        </div>

        @if($overrides)
            <dl class="admin-details">
                @foreach(['translations_per_day', 'translations_per_month', 'explanations_per_day', 'explanations_per_month', 'tts_minutes_per_day', 'tts_minutes_per_month', 'max_translation_characters', 'max_tts_characters_per_request'] as $field)
                    @if($overrides->$field !== null)
                        <div><dt>{{ str_replace('_', ' ', $field) }}</dt><dd>{{ $overrides->$field }}</dd></div>
                    @endif
                @endforeach
                @if($overrides->reason)
                    <div><dt>Reason</dt><dd>{{ $overrides->reason }}</dd></div>
                @endif
                @if($overrides->ends_at)
                    <div><dt>Expires</dt><dd>{{ $overrides->ends_at->format('M d, Y H:i') }}</dd></div>
                @endif
            </dl>
            <form method="POST" action="{{ route('admin.users.remove-override', [$managedUser, $overrides]) }}" onsubmit="return confirm('Remove this override?')">
                @csrf
                <button type="submit" class="admin-danger-button">Remove override</button>
            </form>
        @else
            <p>No active override.</p>
            <details>
                <summary>Create override</summary>
                <form method="POST" action="{{ route('admin.users.store-override', $managedUser) }}" style="margin-top: 0.75rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; max-width: 40rem;">
                    @csrf
                    <label>Translations/day <input type="number" name="translations_per_day" min="0" placeholder="Default"></label>
                    <label>Translations/month <input type="number" name="translations_per_month" min="0" placeholder="Default"></label>
                    <label>Explanations/day <input type="number" name="explanations_per_day" min="0" placeholder="Default"></label>
                    <label>Explanations/month <input type="number" name="explanations_per_month" min="0" placeholder="Default"></label>
                    <label>TTS min/day <input type="number" name="tts_minutes_per_day" min="0" placeholder="Default"></label>
                    <label>TTS min/month <input type="number" name="tts_minutes_per_month" min="0" placeholder="Default"></label>
                    <label>Max translation chars <input type="number" name="max_translation_characters" min="0" placeholder="Default"></label>
                    <label>Max explanation ctx chars <input type="number" name="max_explanation_context_characters" min="0" placeholder="Default"></label>
                    <label>Max TTS chars/request <input type="number" name="max_tts_characters_per_request" min="0" placeholder="Default"></label>
                    <label>Reason <input type="text" name="reason" placeholder="Why?"></label>
                    <label>Starts at <input type="date" name="starts_at"></label>
                    <label>Ends at <input type="date" name="ends_at"></label>
                    <label style="grid-column: span 2"><input type="checkbox" name="ai_translation_enabled" value="1"> AI translation enabled</label>
                    <label style="grid-column: span 2"><input type="checkbox" name="ai_explanation_enabled" value="1"> AI explanation enabled</label>
                    <label style="grid-column: span 2"><input type="checkbox" name="ai_tts_enabled" value="1"> AI TTS enabled</label>
                    <label style="grid-column: span 2"><input type="checkbox" name="browser_tts_enabled" value="1"> Browser TTS enabled</label>
                    <div style="grid-column: span 2"><button type="submit" class="admin-button">Create override</button></div>
                </form>
            </details>
        @endif
    </section>
    @endunless
@endsection
