@extends('admin.layouts.app')

@section('title', 'Word Learning Events')
@section('eyebrow', 'Learning intelligence')

@section('content')
    <form method="GET" class="admin-filters" style="margin-bottom:16px">
        <input name="user_id" placeholder="User ID" value="{{ request('user_id') }}" style="width:100px">
        <select name="event_type">
            <option value="">All types</option>
            @foreach($eventTypes as $t)
                <option value="{{ $t }}" @selected(request('event_type') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Word</th>
                <th>Event</th>
                <th>Book</th>
                <th>Page</th>
                <th>When</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $e)
                <tr>
                    <td>{{ $e->id }}</td>
                    <td>{{ $e->user?->name ?? 'ID:' . $e->user_id }}</td>
                    <td>{{ $e->word?->word ?? 'ID:' . $e->user_word_id }}</td>
                    <td>{{ $e->event_type }}</td>
                    <td>{{ $e->book?->title ?? '—' }}</td>
                    <td>{{ $e->page_number ?? '—' }}</td>
                    <td>{{ $e->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No events yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $events->links() }}
@endsection
