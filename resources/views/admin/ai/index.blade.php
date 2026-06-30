@extends('admin.layouts.app')

@section('title', 'AI & TTS')
@section('eyebrow', 'Intelligence layer')

@section('content')
    <div class="ai-metrics">
        <div class="ai-period-nav">
            <form method="GET" action="{{ route('admin.ai.overview') }}">
                <select name="period" onchange="this.form.submit()">
                    <option value="today" @selected($period === 'today')>Today</option>
                    <option value="yesterday" @selected($period === 'yesterday')>Yesterday</option>
                    <option value="week" @selected($period === 'week')>Last 7 days</option>
                    <option value="month" @selected($period === 'month')>Current month</option>
                    <option value="prev-month" @selected($period === 'prev-month')>Previous month</option>
                </select>
            </form>
        </div>

        <div class="ai-card-grid">
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($overview['operations']) }}</span>
                <small>User operations</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($overview['provider_calls']) }}</span>
                <small>Provider calls</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($overview['cache_hits']) }}</span>
                <small>Cache hits</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($overview['cache_misses']) }}</span>
                <small>Cache misses</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ $overview['cache_hit_rate'] }}%</span>
                <small>Cache hit rate</small>
            </article>
            <article class="ai-card" @if($overview['failed'] > 0) style="--card-accent: var(--red)" @endif>
                <span class="ai-card-value">{{ number_format($overview['failed']) }}</span>
                <small>Failed requests</small>
            </article>
            <article class="ai-card" @if($overview['budget_blocked'] > 0) style="--card-accent: var(--orange)" @endif>
                <span class="ai-card-value">{{ number_format($overview['budget_blocked']) }}</span>
                <small>Budget blocked</small>
            </article>
        </div>

        <div class="ai-card-grid ai-cost-cards">
            <article class="ai-card">
                <span class="ai-card-value">${{ number_format($overview['estimated_cost'], 2) }}</span>
                <small>Estimated cost</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">${{ number_format($overview['saved_cost'], 2) }}</span>
                <small>Saved by cache</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">${{ number_format($overview['cost_without_cache'], 2) }}</span>
                <small>Cost without cache</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ $overview['tts_minutes'] }} min</span>
                <small>TTS duration</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($ttsStorageSize / 1024 / 1024, 1) }} MB</span>
                <small>TTS storage ({{ number_format($ttsFileCount) }} files)</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">≈ {{ number_format($overview['estimated_cost'] * $rate, 2) }} SEK</span>
                <small>Estimated in SEK (1 USD = {{ $rate }} SEK)</small>
            </article>
        </div>

        <div class="ai-breakdown">
            <h3>By operation type</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Operation</th>
                        <th>Count</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overview['breakdown'] as $type => $count)
                        <tr>
                            <td>{{ ucfirst($type) }}</td>
                            <td>{{ number_format($count) }}</td>
                            <td>{{ $overview['operations'] > 0 ? round(($count / $overview['operations']) * 100, 1) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No operations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ai-monthly">
            <h3>Current month totals</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Operations</td><td>{{ number_format($monthly['operations']) }}</td></tr>
                    <tr><td>Provider calls</td><td>{{ number_format($monthly['provider_calls']) }}</td></tr>
                    <tr><td>Cache hits</td><td>{{ number_format($monthly['cache_hits']) }}</td></tr>
                    <tr><td>Cache hit rate</td><td>{{ $monthly['cache_hit_rate'] }}%</td></tr>
                    <tr><td>Estimated cost</td><td>${{ number_format($monthly['estimated_cost'], 2) }}</td></tr>
                    <tr><td>Saved by cache</td><td>${{ number_format($monthly['saved_cost'], 2) }}</td></tr>
                    <tr><td>TTS minutes</td><td>{{ $monthly['tts_duration_ms'] > 0 ? round($monthly['tts_duration_ms'] / 60000, 1) : 0 }} min</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<style>
.ai-metrics { --card-accent: var(--blue); }
.ai-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.ai-cost-cards { margin-top: 24px; }
.ai-card {
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 20px;
    border-left: 3px solid var(--card-accent, var(--blue));
}
.ai-card-value { display: block; font-size: 1.6rem; font-weight: 700; color: var(--ink); }
.ai-card small { display: block; margin-top: 4px; color: var(--muted); font-size: .85rem; }
.ai-period-nav { margin-bottom: 20px; }
.ai-period-nav select { padding: 6px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: .9rem; }
.ai-breakdown, .ai-monthly { margin-top: 28px; }
.ai-breakdown h3, .ai-monthly h3 { margin-bottom: 12px; font-size: 1.1rem; }
</style>
@endpush
