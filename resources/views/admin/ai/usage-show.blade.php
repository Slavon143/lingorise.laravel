@extends('admin.layouts.app')

@section('title', 'Usage event')
@section('eyebrow', 'AI & TTS')

@section('content')
    <a href="{{ route('admin.ai.usage.index') }}" class="admin-back-link">← Back to usage</a>

    <table class="admin-table" style="margin-top: 16px">
        <tbody>
            <tr><th>Request UUID</th><td>{{ $event->request_uuid }}</td></tr>
            <tr><th>User</th><td>{{ $event->user?->name ?? '—' }} (ID: {{ $event->user_id ?? 'N/A' }})</td></tr>
            <tr><th>Book</th><td>{{ $event->book?->title ?? '—' }} (ID: {{ $event->book_id ?? 'N/A' }})</td></tr>
            <tr><th>Operation</th><td>{{ ucfirst($event->operation_type) }}</td></tr>
            <tr><th>Provider</th><td>{{ $event->provider }}</td></tr>
            <tr><th>Model</th><td>{{ $event->model }}</td></tr>
            <tr><th>Cache key</th><td style="font-family: monospace; font-size: .85rem;">{{ $event->cache_key ?? '—' }}</td></tr>
            <tr><th>Cache hit</th><td>@if($event->cache_hit) Yes @else No @endif</td></tr>
            <tr><th>Provider called</th><td>@if($event->provider_called) Yes @else No @endif</td></tr>
            <tr><th>Request characters</th><td>{{ number_format($event->request_characters) }}</td></tr>
            <tr><th>Response characters</th><td>{{ number_format($event->response_characters) }}</td></tr>
            <tr><th>Input tokens</th><td>{{ $event->input_tokens ?? 'N/A' }}</td></tr>
            <tr><th>Output tokens</th><td>{{ $event->output_tokens ?? 'N/A' }}</td></tr>
            <tr><th>Cached input tokens</th><td>{{ $event->cached_input_tokens ?? 'N/A' }}</td></tr>
            <tr><th>Audio duration (ms)</th><td>{{ $event->audio_duration_ms ?? 'N/A' }}</td></tr>
            <tr><th>Audio size (bytes)</th><td>{{ $event->audio_size_bytes ?? 'N/A' }}</td></tr>
            <tr><th>Cost calculation type</th><td>{{ $event->cost_calculation_type }}</td></tr>
            <tr><th>Estimated cost (USD)</th><td>${{ number_format($event->estimated_cost_usd, 8) }}</td></tr>
            <tr><th>Actual cost (USD)</th><td>{{ $event->actual_cost_usd !== null ? '$' . number_format($event->actual_cost_usd, 8) : 'N/A' }}</td></tr>
            <tr><th>Saved cost (USD)</th><td>${{ number_format($event->saved_cost_usd, 8) }}</td></tr>
            <tr><th>Duration (ms)</th><td>{{ $event->duration_ms ?? 'N/A' }}</td></tr>
            <tr><th>Provider duration (ms)</th><td>{{ $event->provider_duration_ms ?? 'N/A' }}</td></tr>
            <tr><th>Status</th><td>{{ $event->status }}</td></tr>
            <tr><th>Error code</th><td>{{ $event->error_code ?? '—' }}</td></tr>
            <tr><th>Safe error</th><td>{{ $event->safe_error_message ?? '—' }}</td></tr>
            <tr><th>Created</th><td>{{ $event->created_at->format('Y-m-d H:i:s') }}</td></tr>
        </tbody>
    </table>
@endsection
