@extends('admin.layouts.app')

@section('title', 'Settings')
@section('eyebrow', 'Safe configuration view')

@section('content')
    @if (session('status'))
        <div class="admin-alert admin-alert-success">{{ session('status') }}</div>
    @endif

    <section class="admin-panel admin-reading-settings">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Reading settings</span>
                <h2>Daily goal</h2>
                <p>Control the default reading goal, safe limits, presets, streaks, and over-goal messaging.</p>
            </div>
        </div>

        <form class="admin-daily-goal-form" method="POST" action="{{ route('admin.settings.daily-goal.update') }}">
            @csrf
            @method('PATCH')

            <div class="admin-form-grid">
                <label>
                    <span>Default daily goal minutes</span>
                    <input type="number" name="default_minutes" min="1" max="1440" value="{{ old('default_minutes', $dailyGoalSettings['default_minutes']) }}" required>
                    @error('default_minutes') <small>{{ $message }}</small> @enderror
                </label>
                <label>
                    <span>Minimum daily goal minutes</span>
                    <input type="number" name="minimum_minutes" min="1" max="1440" value="{{ old('minimum_minutes', $dailyGoalSettings['minimum_minutes']) }}" required>
                    @error('minimum_minutes') <small>{{ $message }}</small> @enderror
                </label>
                <label>
                    <span>Maximum daily goal minutes</span>
                    <input type="number" name="maximum_minutes" min="1" max="1440" value="{{ old('maximum_minutes', $dailyGoalSettings['maximum_minutes']) }}" required>
                    @error('maximum_minutes') <small>{{ $message }}</small> @enderror
                </label>
                <label class="admin-form-wide">
                    <span>Selectable preset values</span>
                    <input type="text" name="preset_minutes" value="{{ old('preset_minutes', implode(', ', $dailyGoalSettings['preset_minutes'])) }}" placeholder="5, 10, 15, 20, 30" required>
                    <small>Comma or space separated integer minutes. Values must fit the min/max range.</small>
                    @error('preset_minutes') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="admin-toggle-row">
                <label><input type="checkbox" name="streak_enabled" value="1" @checked(old('streak_enabled', $dailyGoalSettings['streak_enabled']))> Enable streak</label>
                <label><input type="checkbox" name="over_goal_message_enabled" value="1" @checked(old('over_goal_message_enabled', $dailyGoalSettings['over_goal_message_enabled']))> Enable over-goal message</label>
                <label><input type="checkbox" name="custom_goal_enabled" value="1" @checked(old('custom_goal_enabled', $dailyGoalSettings['custom_goal_enabled']))> Allow custom user goal</label>
            </div>

            <button class="admin-primary-button" type="submit">Save daily goal settings</button>
        </form>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Read-only</span>
                <h2>Application status</h2>
            </div>
        </div>

        <div class="admin-settings-grid">
            @foreach($settings as $label => $value)
                <article>
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </article>
            @endforeach
        </div>

        <div class="admin-form-note">
            Secrets such as APP_KEY, database passwords, OpenAI keys, Stripe secrets, and mail passwords are never displayed here.
        </div>
    </section>
@endsection
