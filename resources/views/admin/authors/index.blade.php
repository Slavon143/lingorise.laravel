@extends('admin.layouts.app')

@section('title', 'Authors')
@section('eyebrow', 'Book authors')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Directory</span>
                <h2>Authors</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.authors.create') }}">+ Add author</a>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.authors.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Author name">
            </label>
            <button type="submit">Search</button>
            <a href="{{ route('admin.authors.index') }}">Reset</a>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Country</th>
                        <th>Years</th>
                        <th>Books</th>
                        <th>Created at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($authors as $author)
                        <tr>
                            <td>#{{ $author->id }}</td>
                            <td><strong>{{ $author->name }}</strong></td>
                            <td>{{ $author->slug }}</td>
                            <td>{{ $author->country ?? '—' }}</td>
                            <td>
                                @if($author->birth_year)
                                    {{ $author->birth_year }}{{ $author->death_year ? '–'.$author->death_year : '' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($author->books_count) }}</td>
                            <td>{{ $author->created_at?->format('M d, Y') }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.authors.edit', $author) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.authors.destroy', $author) }}" onsubmit="return confirm('Delete this author?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:#e74c3c;cursor:pointer;font:inherit;font-weight:700;padding:0;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-empty-cell">No authors yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $authors->links() }}
        </div>
    </section>
@endsection
