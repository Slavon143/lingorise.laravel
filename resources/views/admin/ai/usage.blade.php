@extends('admin.layouts.app')

@section('title', 'Usage events')
@section('eyebrow', 'AI & TTS')

@section('content')
    <form method="GET" class="ai-filter-form" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">
        <label>Period
            <select name="period">
                <option value="">All</option>
                <option value="today" @selected(request('period') === 'today')>Today</option>
                <option value="week" @selected(request('period') === 'week')>Last 7 days</option>
                <option value="month" @selected(request('period') === 'month')>Current month</option>
            </select>
        </label>
        <label>Operation
            <select name="operation">
                <option value="">All</option>
                <option value="translation" @selected(request('operation') === 'translation')>Translation</option>
                <option value="explanation" @selected(request('operation') === 'explanation')>Explanation</option>
                <option value="tts" @selected(request('operation') === 'tts')>TTS</option>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">All</option>
                <option value="success" @selected(request('status') === 'success')>Success</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </label>
        <label><input type="checkbox" name="only_errors" value="1" @checked(request()->boolean('only_errors'))> Only errors</label>
        <button type="submit" class="admin-btn">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Book</th>
                <th>Operation</th>
                <th>Provider</th>
                <th>Model</th>
                <th>Cache</th>
                <th>Provider called</th>
                <th>Chars</th>
                <th>Est. cost</th>
                <th>Saved</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td><a href="{{ route('admin.ai.usage.show', $event) }}">{{ $event->created_at->format('Y-m-d H:i') }}</a></td>
                    <td>{{ $event->user?->name ?? '—' }}</td>
                    <td>{{ $event->book?->title ?? '—' }}</td>
                    <td>{{ ucfirst($event->operation_type) }}</td>
                    <td>{{ $event->provider }}</td>
                    <td style="font-size: .85rem">{{ $event->model }}</td>
                    <td>@if($event->cache_hit)<span class="badge badge-hit">HIT</span>@else<span class="badge badge-miss">MISS</span>@endif</td>
                    <td>@if($event->provider_called)<span class="badge badge-yes">YES</span>@else<span class="badge badge-no">NO</span>@endif</td>
                    <td>{{ number_format($event->request_characters) }}</td>
                    <td>${{ number_format($event->estimated_cost_usd, 4) }}</td>
                    <td>${{ number_format($event->saved_cost_usd, 4) }}</td>
                    <td><span class="badge badge-{{ $event->status }}">{{ $event->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="12">No usage events found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $events->links() }}
@endsection
