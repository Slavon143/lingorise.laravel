@extends('admin.layouts.app')

@section('title', $plan->name)
@section('eyebrow', 'Plan configuration')

@section('content')
    @php
        $reader = $plan->readerSettings;
        $ai = $plan->aiLimits;
        $readerEnabled = old('reader_is_active', $reader?->is_active ?? true);
        $readerSummary = [
            ['label' => 'Translation', 'value' => old('translation_max_words', $reader?->translation_max_words ?? 10) . ' words'],
            ['label' => 'Daily AI', 'value' => old('ai_actions_daily_limit', $reader?->ai_actions_daily_limit ?? 10) . ' actions'],
            ['label' => 'AI TTS', 'value' => old('reader_ai_tts_enabled', $reader?->ai_tts_enabled ?? false) ? 'Enabled' : 'Disabled'],
            ['label' => 'Browser TTS', 'value' => old('reader_browser_tts_enabled', $reader?->browser_tts_enabled ?? true) ? 'Enabled' : 'Disabled'],
        ];
        $readerLimits = [
            ['name' => 'translation_max_words', 'label' => 'Translation', 'hint' => 'Selected words sent for translation', 'default' => 10],
            ['name' => 'context_max_words', 'label' => 'Context', 'hint' => 'Selected phrase for context explanation', 'default' => 6],
            ['name' => 'grammar_max_words', 'label' => 'Grammar', 'hint' => 'Phrase checked for grammar explanation', 'default' => 10],
            ['name' => 'simplify_max_words', 'label' => 'Simplify', 'hint' => 'Text fragment sent to simplification', 'default' => 10],
            ['name' => 'tts_max_words', 'label' => 'Listen / TTS', 'hint' => 'Maximum words for reference voice', 'default' => 10],
            ['name' => 'pronunciation_max_words', 'label' => 'Practice', 'hint' => 'Maximum words for pronunciation practice', 'default' => 10],
            ['name' => 'vocabulary_max_words', 'label' => 'Vocabulary', 'hint' => 'Maximum saved phrase length', 'default' => 10],
        ];
        $readerFeatures = [
            ['name' => 'translation_enabled', 'label' => 'Translation', 'checked' => $reader?->translation_enabled ?? true],
            ['name' => 'context_enabled', 'label' => 'Context', 'checked' => $reader?->context_enabled ?? true],
            ['name' => 'grammar_enabled', 'label' => 'Grammar', 'checked' => $reader?->grammar_enabled ?? true],
            ['name' => 'simplify_enabled', 'label' => 'Simplify', 'checked' => $reader?->simplify_enabled ?? true],
            ['name' => 'vocabulary_enabled', 'label' => 'Vocabulary', 'checked' => $reader?->vocabulary_enabled ?? true],
            ['name' => 'reader_browser_tts_enabled', 'label' => 'Browser TTS', 'checked' => $reader?->browser_tts_enabled ?? true],
            ['name' => 'reader_ai_tts_enabled', 'label' => 'AI TTS', 'checked' => $reader?->ai_tts_enabled ?? false],
            ['name' => 'pronunciation_recording_enabled', 'label' => 'Pronunciation recording', 'checked' => $reader?->pronunciation_recording_enabled ?? true],
            ['name' => 'reader_shadowing_enabled', 'label' => 'Shadowing', 'checked' => $reader?->shadowing_enabled ?? false],
            ['name' => 'voice_selection_enabled', 'label' => 'Voice selection', 'checked' => $reader?->voice_selection_enabled ?? false],
            ['name' => 'daily_goal_enabled', 'label' => 'Daily goal', 'checked' => $reader?->daily_goal_enabled ?? true],
            ['name' => 'streak_enabled', 'label' => 'Reading streak', 'checked' => $reader?->streak_enabled ?? true],
            ['name' => 'import_private_books_enabled', 'label' => 'Private book import', 'checked' => $reader?->import_private_books_enabled ?? true],
            ['name' => 'public_library_enabled', 'label' => 'Public library', 'checked' => $reader?->public_library_enabled ?? true],
            ['name' => 'reader_is_active', 'label' => 'Reader settings active', 'checked' => $readerEnabled],
        ];
    @endphp

    <form class="admin-plan-editor" method="POST" action="{{ route('admin.plans.update', $plan) }}">
        @csrf
        @method('PATCH')

        <section class="admin-plan-hero">
            <div>
                <a class="admin-muted-link" href="{{ route('admin.plans.index') }}">← Back to plans</a>
                <span class="admin-plan-code">{{ $plan->code }}</span>
                <h2>{{ $plan->name }}</h2>
                <p>{{ $plan->description ?: 'Control Reader limits, AI access, and plan visibility from one safe place.' }}</p>
            </div>
            <div class="admin-plan-summary">
                @foreach($readerSummary as $item)
                    <article>
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                    </article>
                @endforeach
            </div>
        </section>

        @unless($plan->isAdmin())
            <div class="admin-plan-warning">
                <strong>Recommended defaults</strong>
                <span>Restore the standard {{ $plan->name }} features and limits without touching name, price, billing interval, Stripe metadata, or subscriptions.</span>
                <button type="submit"
                        form="reset-plan-defaults"
                        onclick="return confirm('Reset {{ $plan->name }} features and limits to recommended defaults?')">
                    Reset to recommended defaults
                </button>
            </div>
        @endunless

        @if($plan->isFree())
            <div class="admin-plan-warning">
                <strong>Free plan cost guard</strong>
                <span>Keep AI TTS disabled unless you intentionally want Free users to trigger paid provider calls.</span>
            </div>
        @endif

        <div class="admin-plan-grid">
            <section class="admin-plan-card">
                <div class="admin-plan-card-head">
                    <span>01</span>
                    <div>
                        <h3>Plan identity</h3>
                        <p>Public name, status, display order and pricing metadata.</p>
                    </div>
                </div>

                <div class="admin-plan-fields two">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name', $plan->name) }}" required>
                    </label>
                    <label>
                        <span>Position</span>
                        <input type="number" name="position" value="{{ old('position', $plan->position) }}" min="0">
                    </label>
                    <label class="wide">
                        <span>Description</span>
                        <textarea name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                    </label>
                    <label>
                        <span>Price</span>
                        <input type="number" name="price_amount" value="{{ old('price_amount', $plan->price_amount) }}" min="0" step="0.01" placeholder="0.00">
                    </label>
                    <label>
                        <span>Currency</span>
                        <input type="text" name="price_currency" value="{{ old('price_currency', $plan->price_currency) }}" maxlength="3" placeholder="USD">
                    </label>
                    <label>
                        <span>Interval</span>
                        <select name="billing_interval">
                            <option value="">None</option>
                            <option value="month" @selected(old('billing_interval', $plan->billing_interval) === 'month')>Monthly</option>
                            <option value="year" @selected(old('billing_interval', $plan->billing_interval) === 'year')>Yearly</option>
                        </select>
                    </label>
                </div>

                <div class="admin-plan-toggles">
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))><span>Active plan</span></label>
                    <label><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $plan->is_default))><span>Default plan</span></label>
                </div>
            </section>

            <section class="admin-plan-card is-reader">
                <div class="admin-plan-card-head">
                    <span>02</span>
                    <div>
                        <h3>Reader word limits</h3>
                        <p>These limits are enforced by backend and only mirrored in the Reader UI.</p>
                    </div>
                </div>

                <div class="admin-limit-list">
                    @foreach($readerLimits as $limit)
                        <label>
                            <span>
                                <strong>{{ $limit['label'] }}</strong>
                                <small>{{ $limit['hint'] }}</small>
                            </span>
                            <input type="number" name="{{ $limit['name'] }}" value="{{ old($limit['name'], $reader?->{$limit['name']} ?? $limit['default']) }}" min="1" max="200" required>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="admin-plan-card">
                <div class="admin-plan-card-head">
                    <span>03</span>
                    <div>
                        <h3>Reader AI budget</h3>
                        <p>Daily actions affect AI Reader tools. AI TTS characters control monthly paid voice usage.</p>
                    </div>
                </div>

                <div class="admin-plan-fields two">
                    <label>
                        <span>Daily AI actions</span>
                        <input type="number" name="ai_actions_daily_limit" value="{{ old('ai_actions_daily_limit', $reader?->ai_actions_daily_limit ?? 10) }}" min="0" required>
                    </label>
                    <label>
                        <span>Monthly AI actions</span>
                        <input type="number" name="ai_actions_monthly_limit" value="{{ old('ai_actions_monthly_limit', $reader?->ai_actions_monthly_limit) }}" min="0" placeholder="Unlimited">
                    </label>
                    <label>
                        <span>Monthly AI TTS characters</span>
                        <input type="number" name="ai_tts_monthly_characters" value="{{ old('ai_tts_monthly_characters', $reader?->ai_tts_monthly_characters) }}" min="0" placeholder="Unlimited">
                    </label>
                    <label>
                        <span>Vocabulary entries limit</span>
                        <input type="number" name="vocabulary_entries_limit" value="{{ old('vocabulary_entries_limit', $reader?->vocabulary_entries_limit) }}" min="1" placeholder="Unlimited">
                    </label>
                    <label>
                        <span>Private books limit</span>
                        <input type="number" name="reader_private_books_limit" value="{{ old('reader_private_books_limit', $reader?->private_books_limit) }}" min="1" placeholder="Unlimited">
                    </label>
                </div>
            </section>

            <section class="admin-plan-card">
                <div class="admin-plan-card-head">
                    <span>04</span>
                    <div>
                        <h3>Reader features</h3>
                        <p>Switch Reader capabilities on or off for this plan.</p>
                    </div>
                </div>

                <div class="admin-feature-grid">
                    @foreach($readerFeatures as $feature)
                        <label>
                            <input type="checkbox" name="{{ $feature['name'] }}" value="1" @checked(old($feature['name'], $feature['checked']))>
                            <span>{{ $feature['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="admin-plan-card">
                <div class="admin-plan-card-head">
                    <span>05</span>
                    <div>
                        <h3>Provider quotas</h3>
                        <p>Legacy provider-call limits used by the Intelligence layer.</p>
                    </div>
                </div>

                <div class="admin-plan-fields three">
                    <label><span>Translations / day</span><input type="number" name="translations_per_day" value="{{ old('translations_per_day', $ai?->translations_per_day) }}" min="0" placeholder="∞"></label>
                    <label><span>Translations / month</span><input type="number" name="translations_per_month" value="{{ old('translations_per_month', $ai?->translations_per_month) }}" min="0" placeholder="∞"></label>
                    <label><span>Max translation chars</span><input type="number" name="max_translation_characters" value="{{ old('max_translation_characters', $ai?->max_translation_characters ?? 500) }}" min="0" required></label>
                    <label><span>Explanations / day</span><input type="number" name="explanations_per_day" value="{{ old('explanations_per_day', $ai?->explanations_per_day) }}" min="0" placeholder="∞"></label>
                    <label><span>Explanations / month</span><input type="number" name="explanations_per_month" value="{{ old('explanations_per_month', $ai?->explanations_per_month) }}" min="0" placeholder="∞"></label>
                    <label><span>Max explanation chars</span><input type="number" name="max_explanation_context_characters" value="{{ old('max_explanation_context_characters', $ai?->max_explanation_context_characters ?? 1000) }}" min="0" required></label>
                    <label><span>Context / day</span><input type="number" name="context_explanations_per_day" value="{{ old('context_explanations_per_day', $ai?->context_explanations_per_day) }}" min="0" placeholder="∞"></label>
                    <label><span>Grammar / day</span><input type="number" name="grammar_explanations_per_day" value="{{ old('grammar_explanations_per_day', $ai?->grammar_explanations_per_day) }}" min="0" placeholder="∞"></label>
                    <label><span>Simplify / day</span><input type="number" name="simplifications_per_day" value="{{ old('simplifications_per_day', $ai?->simplifications_per_day) }}" min="0" placeholder="∞"></label>
                </div>
            </section>

            <section class="admin-plan-card">
                <div class="admin-plan-card-head">
                    <span>06</span>
                    <div>
                        <h3>Voice and access</h3>
                        <p>Provider TTS limits, request pressure, and library access controls.</p>
                    </div>
                </div>

                <div class="admin-plan-fields three">
                    <label><span>TTS minutes / day</span><input type="number" name="tts_minutes_per_day" value="{{ old('tts_minutes_per_day', $ai?->tts_minutes_per_day) }}" min="0" placeholder="∞"></label>
                    <label><span>TTS minutes / month</span><input type="number" name="tts_minutes_per_month" value="{{ old('tts_minutes_per_month', $ai?->tts_minutes_per_month) }}" min="0" placeholder="∞"></label>
                    <label><span>Max TTS chars</span><input type="number" name="max_tts_characters_per_request" value="{{ old('max_tts_characters_per_request', $ai?->max_tts_characters_per_request ?? 500) }}" min="0" required></label>
                    <label><span>Requests / minute</span><input type="number" name="requests_per_minute" value="{{ old('requests_per_minute', $ai?->requests_per_minute ?? 10) }}" min="0" required></label>
                    <label><span>Concurrent TTS</span><input type="number" name="concurrent_tts_requests" value="{{ old('concurrent_tts_requests', $ai?->concurrent_tts_requests ?? 1) }}" min="0" required></label>
                    <label><span>Private books limit</span><input type="number" name="private_books_limit" value="{{ old('private_books_limit', $ai?->private_books_limit) }}" min="0" placeholder="∞"></label>
                    <label><span>Max selected chars</span><input type="number" name="max_explanation_selected_characters" value="{{ old('max_explanation_selected_characters', $ai?->max_explanation_selected_characters ?? 255) }}" min="0"></label>
                    <label><span>Max context chars</span><input type="number" name="max_context_explanation_characters" value="{{ old('max_context_explanation_characters', $ai?->max_context_explanation_characters ?? 1000) }}" min="0"></label>
                    <label><span>Max grammar chars</span><input type="number" name="max_grammar_explanation_characters" value="{{ old('max_grammar_explanation_characters', $ai?->max_grammar_explanation_characters ?? 1500) }}" min="0"></label>
                    <label><span>Max simplify chars</span><input type="number" name="max_simplification_characters" value="{{ old('max_simplification_characters', $ai?->max_simplification_characters ?? 4000) }}" min="0"></label>
                </div>

                <div class="admin-feature-grid compact">
                    <label><input type="checkbox" name="ai_translation_enabled" value="1" @checked(old('ai_translation_enabled', $ai?->ai_translation_enabled ?? true))><span>AI Translation</span></label>
                    <label><input type="checkbox" name="ai_explanation_enabled" value="1" @checked(old('ai_explanation_enabled', $ai?->ai_explanation_enabled ?? true))><span>AI Explanation</span></label>
                    <label><input type="checkbox" name="ai_context_explanation_enabled" value="1" @checked(old('ai_context_explanation_enabled', $ai?->ai_context_explanation_enabled ?? true))><span>AI Context</span></label>
                    <label><input type="checkbox" name="ai_grammar_explanation_enabled" value="1" @checked(old('ai_grammar_explanation_enabled', $ai?->ai_grammar_explanation_enabled ?? true))><span>AI Grammar</span></label>
                    <label><input type="checkbox" name="ai_simplification_enabled" value="1" @checked(old('ai_simplification_enabled', $ai?->ai_simplification_enabled ?? true))><span>AI Simplify</span></label>
                    <label><input type="checkbox" name="ai_tts_enabled" value="1" @checked(old('ai_tts_enabled', $ai?->ai_tts_enabled ?? false))><span>Provider AI TTS</span></label>
                    <label><input type="checkbox" name="browser_tts_enabled" value="1" @checked(old('browser_tts_enabled', $ai?->browser_tts_enabled ?? true))><span>Provider Browser fallback</span></label>
                    <label><input type="checkbox" name="premium_books_enabled" value="1" @checked(old('premium_books_enabled', $ai?->premium_books_enabled ?? false))><span>Premium books</span></label>
                    <label><input type="checkbox" name="shadowing_enabled" value="1" @checked(old('shadowing_enabled', $ai?->shadowing_enabled ?? false))><span>Legacy shadowing</span></label>
                </div>
            </section>
        </div>

        <div class="admin-plan-savebar">
            <div>
                <strong>Save {{ $plan->name }} settings</strong>
                <span>Changes are audited and applied immediately to Reader backend checks.</span>
            </div>
            <button type="submit" class="admin-primary-button">Save changes</button>
        </div>
    </form>

    @unless($plan->isAdmin())
        <form id="reset-plan-defaults" method="POST" action="{{ route('admin.plans.reset-defaults', $plan) }}" hidden>
            @csrf
        </form>
    @endunless
@endsection
