@extends('admin.layouts.app')

@section('title', 'TTS cache detail')
@section('eyebrow', 'AI & TTS')

@section('content')
    <a href="{{ route('admin.ai.cache.tts.index') }}" class="admin-back-link">← Back</a>

    <table class="admin-table" style="margin-top: 16px">
        <tbody>
            <tr><th>Cache key</th><td style="font-family: monospace; font-size: .85rem;">{{ $ttsCache->cache_key }}</td></tr>
            <tr><th>Source text</th><td style="max-width: 500px; word-break: break-word;">{{ $ttsCache->source_text }}</td></tr>
            <tr><th>Language</th><td>{{ $ttsCache->language }}</td></tr>
            <tr><th>Voice</th><td>{{ $ttsCache->voice }}</td></tr>
            <tr><th>Speed</th><td>{{ $ttsCache->speed }}</td></tr>
            <tr><th>Model</th><td>{{ $ttsCache->model }}</td></tr>
            <tr><th>Format</th><td>{{ $ttsCache->format }}</td></tr>
            <tr><th>File path</th><td style="font-family: monospace; font-size: .85rem;">{{ $ttsCache->file_path }}</td></tr>
            <tr><th>File size</th><td>{{ $ttsCache->file_size ? number_format($ttsCache->file_size / 1024, 1) . ' KB' : '—' }}</td></tr>
            <tr><th>Duration</th><td>{{ $ttsCache->duration_ms ? round($ttsCache->duration_ms / 1000, 1) . 's' : '—' }}</td></tr>
            <tr><th>Status</th><td>{{ $ttsCache->status ?? 'ready' }}</td></tr>
            <tr><th>Generation attempts</th><td>{{ $ttsCache->generation_attempts ?? 0 }}</td></tr>
            <tr><th>Hits</th><td>{{ number_format($ttsCache->hits) }}</td></tr>
            <tr><th>Last used</th><td>{{ $ttsCache->last_used_at?->format('Y-m-d H:i:s') ?? 'never' }}</td></tr>
            <tr><th>Created</th><td>{{ $ttsCache->created_at->format('Y-m-d H:i:s') }}</td></tr>
            @if($ttsCache->error_code)
                <tr><th>Error code</th><td>{{ $ttsCache->error_code }}</td></tr>
                <tr><th>Error message</th><td>{{ $ttsCache->error_message }}</td></tr>
            @endif
        </tbody>
    </table>

    <form method="POST" action="{{ route('admin.ai.cache.tts.destroy', $ttsCache) }}" style="margin-top: 16px" onsubmit="return confirm('Delete this cache entry and the audio file?')">
        @csrf @method('DELETE')
        <button type="submit" class="admin-btn admin-btn-danger">Delete entry and file</button>
    </form>
@endsection
