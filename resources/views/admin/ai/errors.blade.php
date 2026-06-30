@extends('admin.layouts.app')

@section('title', 'AI errors')
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
        <button type="submit" class="admin-btn">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Operation</th>
                <th>Model</th>
                <th>Error code</th>
                <th>Safe message</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>{{ $event->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $event->user?->name ?? '—' }}</td>
                    <td>{{ ucfirst($event->operation_type) }}</td>
                    <td>{{ $event->model }}</td>
                    <td style="font-family: monospace; font-size: .85rem;">{{ $event->error_code ?? '—' }}</td>
                    <td style="max-width: 400px; word-break: break-word;">{{ $event->safe_error_message ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No errors.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $events->links() }}
@endsection
