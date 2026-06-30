@extends('admin.layouts.app')

@section('title', 'Translation cache detail')
@section('eyebrow', 'AI & TTS')

@section('content')
    <a href="{{ route('admin.ai.cache.translations.index') }}" class="admin-back-link">← Back</a>

    <table class="admin-table" style="margin-top: 16px">
        <tbody>
            <tr><th>Cache key</th><td style="font-family: monospace; font-size: .85rem;">{{ $entry->cache_key }}</td></tr>
            <tr><th>Source text</th><td>{{ $entry->source_text }}</td></tr>
            <tr><th>Source language</th><td>{{ $entry->source_language }}</td></tr>
            <tr><th>Target language</th><td>{{ $entry->target_language }}</td></tr>
            <tr><th>Translated text</th><td>{{ $entry->translated_text }}</td></tr>
            <tr><th>Pronunciation</th><td>{{ $entry->pronunciation ?? '—' }}</td></tr>
            <tr><th>Model</th><td>{{ $entry->model }}</td></tr>
            <tr><th>Provider</th><td>{{ $entry->provider ?? 'openai' }}</td></tr>
            <tr><th>Prompt version</th><td>{{ $entry->prompt_version }}</td></tr>
            <tr><th>Mode</th><td>{{ $entry->mode ?? '—' }}</td></tr>
            <tr><th>Hits</th><td>{{ number_format($entry->hits) }}</td></tr>
            <tr><th>Last used</th><td>{{ $entry->last_used_at?->format('Y-m-d H:i:s') ?? 'never' }}</td></tr>
            <tr><th>Created</th><td>{{ $entry->created_at->format('Y-m-d H:i:s') }}</td></tr>
            @if($entry->source_characters)<tr><th>Source characters</th><td>{{ number_format($entry->source_characters) }}</td></tr>@endif
            @if($entry->response_characters)<tr><th>Response characters</th><td>{{ number_format($entry->response_characters) }}</td></tr>@endif
        </tbody>
    </table>

    <form method="POST" action="{{ route('admin.ai.cache.translations.destroy', $entry) }}" style="margin-top: 16px" onsubmit="return confirm('Delete this cache entry?')">
        @csrf @method('DELETE')
        <button type="submit" class="admin-btn admin-btn-danger">Delete entry</button>
    </form>
@endsection
