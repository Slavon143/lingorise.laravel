@extends('admin.layouts.app')

@section('title', 'Usage by users')
@section('eyebrow', 'AI & TTS')

@section('content')
    <form method="GET" style="margin-bottom: 20px;">
        <label>Period
            <select name="period" onchange="this.form.submit()">
                <option value="today" @selected(request('period', 'month') === 'today')>Today</option>
                <option value="week" @selected(request('period') === 'week')>Last 7 days</option>
                <option value="month" @selected(request('period', 'month') === 'month')>Current month</option>
            </select>
        </label>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Operations</th>
                <th>Provider calls</th>
                <th>Cache hits</th>
                <th>Cache hit rate</th>
                <th>Estimated cost</th>
                <th>Saved cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $row)
                <tr>
                    <td>{{ $row['user_id'] ?? '—' }}</td>
                    <td>{{ number_format($row['operations']) }}</td>
                    <td>{{ number_format($row['provider_calls']) }}</td>
                    <td>{{ number_format($row['cache_hits']) }}</td>
                    <td>{{ $row['operations'] > 0 ? round(($row['cache_hits'] / $row['operations']) * 100, 1) : 0 }}%</td>
                    <td>${{ number_format($row['estimated_cost'], 4) }}</td>
                    <td>${{ number_format($row['saved_cost'], 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No data.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
