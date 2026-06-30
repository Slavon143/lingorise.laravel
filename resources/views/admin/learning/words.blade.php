@extends('admin.layouts.app')

@section('title', 'User Words')
@section('eyebrow', 'Learning intelligence')

@section('content')
    <form method="GET" class="admin-filters" style="margin-bottom:16px">
        <input name="search" placeholder="Search word..." value="{{ request('search') }}">
        <select name="status">
            <option value="">All statuses</option>
            @foreach(['unknown', 'seen', 'learning', 'known', 'mastered'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <input name="language" placeholder="Language" value="{{ request('language') }}" style="width:100px">
        <input name="user_id" placeholder="User ID" value="{{ request('user_id') }}" style="width:100px">
        <button type="submit">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Word</th>
                <th>Language</th>
                <th>Status</th>
                <th>Mastery</th>
                <th>Seen</th>
                <th>Last seen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($words as $w)
                <tr>
                    <td>{{ $w->id }}</td>
                    <td>{{ $w->user?->name ?? 'ID:' . $w->user_id }}</td>
                    <td>{{ $w->word }}</td>
                    <td>{{ $w->language }}</td>
                    <td>{{ $w->status }}</td>
                    <td>{{ round($w->mastery_score, 1) }}</td>
                    <td>{{ $w->seen_count }}</td>
                    <td>{{ $w->last_seen_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No words found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $words->links() }}
@endsection
