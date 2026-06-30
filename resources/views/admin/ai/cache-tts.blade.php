@extends('admin.layouts.app')

@section('title', 'TTS cache')
@section('eyebrow', 'AI & TTS')

@section('content')
    <form method="GET" style="margin-bottom: 20px;">
        <label>Status
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="ready" @selected(request('status') === 'ready')>Ready</option>
                <option value="generating" @selected(request('status') === 'generating')>Generating</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                <option value="missing" @selected(request('status') === 'missing')>Missing</option>
            </select>
        </label>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Text (preview)</th>
                <th>Language</th>
                <th>Voice</th>
                <th>Speed</th>
                <th>Duration</th>
                <th>Size</th>
                <th>Hits</th>
                <th>Status</th>
                <th>Last used</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td><a href="{{ route('admin.ai.cache.tts.show', $entry) }}">{{ \Illuminate\Support\Str::limit($entry->source_text, 50) }}</a></td>
                    <td>{{ $entry->language }}</td>
                    <td>{{ $entry->voice }}</td>
                    <td>{{ $entry->speed }}</td>
                    <td>{{ $entry->duration_ms ? round($entry->duration_ms / 1000, 1) . 's' : '—' }}</td>
                    <td>{{ $entry->file_size ? number_format($entry->file_size / 1024, 1) . ' KB' : '—' }}</td>
                    <td>{{ number_format($entry->hits) }}</td>
                    <td><span class="badge badge-{{ $entry->status ?? 'ready' }}">{{ $entry->status ?? 'ready' }}</span></td>
                    <td>{{ $entry->last_used_at?->diffForHumans() ?? 'never' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.ai.cache.tts.destroy', $entry) }}" onsubmit="return confirm('Delete this cache entry and file?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-btn-small admin-btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10">No TTS cache entries.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
@endsection
