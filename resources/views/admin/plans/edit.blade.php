@extends('admin.layouts.app')

@section('title', 'Edit ' . $plan->name)
@section('eyebrow', 'Plan configuration')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">{{ $plan->code }}</span>
                <h2>{{ $plan->name }}</h2>
            </div>
            <a href="{{ route('admin.plans.index') }}">&larr; Back to plans</a>
        </div>

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
            @csrf
            @method('PATCH')

            <fieldset class="admin-fieldset">
                <legend>General</legend>

                <label>Name <input type="text" name="name" value="{{ old('name', $plan->name) }}" required></label>
                <label>Description <textarea name="description" rows="2">{{ old('description', $plan->description) }}</textarea></label>

                <div class="admin-checkbox-group">
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))> Active</label>
                    <label><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $plan->is_default))> Default plan</label>
                </div>

                <label>Position <input type="number" name="position" value="{{ old('position', $plan->position) }}" min="0"></label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>Pricing (informational)</legend>
                <label>Price <input type="number" name="price_amount" value="{{ old('price_amount', $plan->price_amount) }}" min="0" step="0.01" placeholder="0.00"></label>
                <label>Currency <input type="text" name="price_currency" value="{{ old('price_currency', $plan->price_currency) }}" size="3" maxlength="3" placeholder="USD"></label>
                <label>Interval
                    <select name="billing_interval">
                        <option value="">None</option>
                        <option value="month" @selected(old('billing_interval', $plan->billing_interval) === 'month')>Monthly</option>
                        <option value="year" @selected(old('billing_interval', $plan->billing_interval) === 'year')>Yearly</option>
                    </select>
                </label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>AI Translation limits</legend>
                <div class="admin-fieldset-grid">
                    <label>Per day <input type="number" name="translations_per_day" value="{{ old('translations_per_day', $plan->aiLimits?->translations_per_day) }}" min="0" placeholder="Unlimited"></label>
                    <label>Per month <input type="number" name="translations_per_month" value="{{ old('translations_per_month', $plan->aiLimits?->translations_per_month) }}" min="0" placeholder="Unlimited"></label>
                </div>
                <label>Max characters per request <input type="number" name="max_translation_characters" value="{{ old('max_translation_characters', $plan->aiLimits?->max_translation_characters) }}" min="0" placeholder="Unlimited"></label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>AI Explanation limits</legend>
                <div class="admin-fieldset-grid">
                    <label>Per day <input type="number" name="explanations_per_day" value="{{ old('explanations_per_day', $plan->aiLimits?->explanations_per_day) }}" min="0" placeholder="Unlimited"></label>
                    <label>Per month <input type="number" name="explanations_per_month" value="{{ old('explanations_per_month', $plan->aiLimits?->explanations_per_month) }}" min="0" placeholder="Unlimited"></label>
                </div>
                <label>Max context characters <input type="number" name="max_explanation_context_characters" value="{{ old('max_explanation_context_characters', $plan->aiLimits?->max_explanation_context_characters) }}" min="0" placeholder="Unlimited"></label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>AI TTS limits</legend>
                <div class="admin-fieldset-grid">
                    <label>Minutes per day <input type="number" name="tts_minutes_per_day" value="{{ old('tts_minutes_per_day', $plan->aiLimits?->tts_minutes_per_day) }}" min="0" placeholder="Unlimited"></label>
                    <label>Minutes per month <input type="number" name="tts_minutes_per_month" value="{{ old('tts_minutes_per_month', $plan->aiLimits?->tts_minutes_per_month) }}" min="0" placeholder="Unlimited"></label>
                </div>
                <label>Max characters per request <input type="number" name="max_tts_characters_per_request" value="{{ old('max_tts_characters_per_request', $plan->aiLimits?->max_tts_characters_per_request) }}" min="0" placeholder="Unlimited"></label>
                <label>Concurrent requests <input type="number" name="concurrent_tts_requests" value="{{ old('concurrent_tts_requests', $plan->aiLimits?->concurrent_tts_requests) }}" min="0" placeholder="Unlimited"></label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>Rate limiting</legend>
                <label>Requests per minute <input type="number" name="requests_per_minute" value="{{ old('requests_per_minute', $plan->aiLimits?->requests_per_minute) }}" min="0" placeholder="Unlimited"></label>
            </fieldset>

            <fieldset class="admin-fieldset">
                <legend>Feature toggles</legend>
                <div class="admin-checkbox-group">
                    <label><input type="checkbox" name="ai_translation_enabled" value="1" @checked(old('ai_translation_enabled', $plan->aiLimits?->ai_translation_enabled ?? true))> AI Translation</label>
                    <label><input type="checkbox" name="ai_explanation_enabled" value="1" @checked(old('ai_explanation_enabled', $plan->aiLimits?->ai_explanation_enabled ?? true))> AI Explanation</label>
                    <label><input type="checkbox" name="ai_tts_enabled" value="1" @checked(old('ai_tts_enabled', $plan->aiLimits?->ai_tts_enabled ?? false))> AI TTS</label>
                    <label><input type="checkbox" name="browser_tts_enabled" value="1" @checked(old('browser_tts_enabled', $plan->aiLimits?->browser_tts_enabled ?? true))> Browser TTS</label>
                    <label><input type="checkbox" name="premium_books_enabled" value="1" @checked(old('premium_books_enabled', $plan->aiLimits?->premium_books_enabled ?? false))> Premium books</label>
                </div>
                <label>Private books limit <input type="number" name="private_books_limit" value="{{ old('private_books_limit', $plan->aiLimits?->private_books_limit) }}" min="-1" placeholder="Unlimited"></label>
            </fieldset>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button">Save changes</button>
            </div>
        </form>
    </section>
@endsection
