@extends('admin.layouts.app')

@section('title', 'Translation cache')
@section('eyebrow', 'AI & TTS')

@section('content')
    <form method="GET" style="margin-bottom: 20px;">
        <input type="search" name="q" placeholder="Search source or translation…" value="{{ request('q') }}" style="padding: 6px 12px; border: 1px solid var(--line); border-radius: 8px;">
        <button type="submit" class="admin-btn">Search</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Source (preview)</th>
                <th>Source lang</th>
                <th>Target lang</th>
                <th>Model</th>
                <th>Hits</th>
                <th>Last used</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td><a href="{{ route('admin.ai.cache.translations.show', $entry) }}">{{ \Illuminate\Support\Str::limit($entry->source_text, 60) }}</a></td>
                    <td>{{ $entry->source_language }}</td>
                    <td>{{ $entry->target_language }}</td>
                    <td style="font-size: .85rem">{{ $entry->model }}</td>
                    <td>{{ number_format($entry->hits) }}</td>
                    <td>{{ $entry->last_used_at?->diffForHumans() ?? 'never' }}</td>
                    <td>{{ $entry->created_at->format('Y-m-d') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.ai.cache.translations.destroy', $entry) }}" onsubmit="return confirm('Delete this cache entry?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-btn-small admin-btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No cache entries.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
@endsection
