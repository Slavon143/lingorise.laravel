@extends('admin.layouts.app')

@section('title', 'Learning Intelligence')
@section('eyebrow', 'Differentiation layer')

@section('content')
    <div class="ai-metrics">
        <div class="ai-card-grid">
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($totalWords) }}</span>
                <small>Words tracked</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($totalEvents) }}</span>
                <small>Learning events</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($totalShadowing) }}</span>
                <small>Shadowing attempts</small>
            </article>
            <article class="ai-card">
                <span class="ai-card-value">{{ number_format($totalCache) }}</span>
                <small>AI structured cache entries</small>
            </article>
        </div>

        <div class="ai-breakdown">
            <h3>Words by status</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statusBreakdown as $status => $count)
                        <tr>
                            <td>{{ ucfirst($status) }}</td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2">No words tracked yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ai-section">
            <h3>Recent learning events</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Word</th>
                        <th>Event</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentEvents as $event)
                        <tr>
                            <td>{{ $event->user?->name ?? '—' }}</td>
                            <td>{{ $event->word?->word ?? '—' }}</td>
                            <td>{{ $event->event_type }}</td>
                            <td>{{ $event->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ai-nav-links" style="margin-top:24px">
            <a href="{{ route('admin.learning.words') }}">Browse words →</a>
            <a href="{{ route('admin.learning.shadowing') }}" style="margin-left:16px">Shadowing attempts →</a>
            <a href="{{ route('admin.learning.cache') }}" style="margin-left:16px">AI cache →</a>
            <a href="{{ route('admin.learning.events') }}" style="margin-left:16px">Events →</a>
        </div>
    </div>
@endsection
