@extends('admin.layouts.app')

@section('title', 'Explanation cache detail')
@section('eyebrow', 'AI & TTS')

@section('content')
    <a href="{{ route('admin.ai.cache.explanations.index') }}" class="admin-back-link">← Back</a>

    <table class="admin-table" style="margin-top: 16px">
        <tbody>
            <tr><th>Cache key</th><td style="font-family: monospace; font-size: .85rem;">{{ $entry->cache_key }}</td></tr>
            <tr><th>Selected text</th><td>{{ $entry->selected_text }}</td></tr>
            <tr><th>Context</th><td style="max-width: 600px; word-break: break-word;">{{ $entry->context_text }}</td></tr>
            <tr><th>Source language</th><td>{{ $entry->source_language }}</td></tr>
            <tr><th>Target language</th><td>{{ $entry->target_language }}</td></tr>
            <tr><th>Explanation</th><td style="max-width: 600px;">{{ $entry->explanation_text }}</td></tr>
            <tr><th>Model</th><td>{{ $entry->model }}</td></tr>
            <tr><th>Prompt version</th><td>{{ $entry->prompt_version }}</td></tr>
            <tr><th>Hits</th><td>{{ number_format($entry->hits) }}</td></tr>
            <tr><th>Last used</th><td>{{ $entry->last_used_at?->format('Y-m-d H:i:s') ?? 'never' }}</td></tr>
            <tr><th>Created</th><td>{{ $entry->created_at->format('Y-m-d H:i:s') }}</td></tr>
        </tbody>
    </table>

    <form method="POST" action="{{ route('admin.ai.cache.explanations.destroy', $entry) }}" style="margin-top: 16px" onsubmit="return confirm('Delete this cache entry?')">
        @csrf @method('DELETE')
        <button type="submit" class="admin-btn admin-btn-danger">Delete entry</button>
    </form>
@endsection
