@extends('admin.layouts.app')

@section('title', 'Shadowing Attempts')
@section('eyebrow', 'Learning intelligence')

@section('content')
    <form method="GET" class="admin-filters" style="margin-bottom:16px">
        <input name="user_id" placeholder="User ID" value="{{ request('user_id') }}" style="width:100px">
        <input name="book_id" placeholder="Book ID" value="{{ request('book_id') }}" style="width:100px">
        <button type="submit">Filter</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Book</th>
                <th>Page</th>
                <th>Words</th>
                <th>Attempts</th>
                <th>Rating</th>
                <th>When</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attempts as $a)
                <tr>
                    <td>{{ $a->id }}</td>
                    <td>{{ $a->user?->name ?? 'ID:' . $a->user_id }}</td>
                    <td>{{ $a->book?->title ?? 'ID:' . $a->book_id }}</td>
                    <td>{{ $a->page_number }}</td>
                    <td>{{ $a->word_index_start }}-{{ $a->word_index_end }}</td>
                    <td>{{ $a->attempts_count }}</td>
                    <td>{{ $a->self_rating ?? '—' }}</td>
                    <td>{{ $a->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No shadowing attempts yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $attempts->links() }}
@endsection
