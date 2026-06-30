@extends('admin.layouts.app')

@section('title', 'Books')
@section('eyebrow', 'Content operations')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Catalogue</span>
                <h2>Books</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.books.create') }}">+ Create book</a>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.books.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Title or author">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="ready" @selected(request('status') === 'ready')>Ready</option>
                    <option value="published" @selected(request('status') === 'published')>Published</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
            </label>
            <label>
                <span>Access</span>
                <select name="access_type">
                    <option value="">All</option>
                    <option value="public" @selected(request('access_type') === 'public')>Public</option>
                    <option value="premium" @selected(request('access_type') === 'premium')>Premium</option>
                    <option value="private" @selected(request('access_type') === 'private')>Private</option>
                </select>
            </label>
            <label>
                <span>Language</span>
                <select name="language_id">
                    <option value="">All</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->id }}" @selected((int) request('language_id') === $lang->id)>{{ $lang->name }} ({{ $lang->code }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Category</span>
                <select name="category_id">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) request('category_id') === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Featured</span>
                <select name="featured">
                    <option value="">All</option>
                    <option value="yes" @selected(request('featured') === 'yes')>Featured</option>
                    <option value="no" @selected(request('featured') === 'no')>Not featured</option>
                </select>
            </label>
            <label>
                <span>Sort by</span>
                <select name="sort">
                    @foreach(['created_at' => 'Created at', 'updated_at' => 'Updated at', 'published_at' => 'Published at', 'title' => 'Title', 'author' => 'Author', 'language_locale' => 'Language', 'level' => 'Level', 'difficulty' => 'Difficulty', 'access_type' => 'Access', 'processing_status' => 'Status', 'total_words' => 'Word count', 'is_featured' => 'Featured', 'id' => 'ID'] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Direction</span>
                <select name="direction">
                    <option value="desc" @selected($direction === 'desc')>Newest first</option>
                    <option value="asc" @selected($direction === 'asc')>Oldest first</option>
                </select>
            </label>
            <button type="submit">Apply filters</button>
            <a href="{{ route('admin.books.index') }}">Reset</a>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Owner</th>
                        <th>Access</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Published at</th>
                        <th>Created at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        <tr>
                            <td>#{{ $book->id }}</td>
                            <td>
                                @if($book->cover_path)
                                    <img src="{{ Storage::url($book->cover_path) }}" alt="" style="width:40px;height:56px;object-fit:cover;border-radius:6px;">
                                @else
                                    <span style="display:inline-flex;width:40px;height:56px;background:#eef0f4;border-radius:6px;align-items:center;justify-content:center;font-size:10px;color:#999;">—</span>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.books.show', $book) }}">{{ $book->title }}</a></td>
                            <td>{{ $book->authorRelation?->name ?? $book->author ?? '—' }}</td>
                            <td>{{ $book->categoryRelation?->name ?? $book->category ?? '—' }}</td>
                            <td>{{ $book->languageRelation?->code ?? $book->language_locale ?? '—' }}</td>
                            <td>
                                @php $owner = $book->owner ?? $book->bookOwner; @endphp
                                @if($owner)
                                    <a href="{{ route('admin.users.show', $owner) }}">{{ $owner->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="admin-badge {{ $book->access_type === 'public' ? 'is-public' : ($book->access_type === 'premium' ? 'is-premium' : '') }}">
                                    {{ ucfirst($book->access_type ?? 'private') }}
                                </span>
                            </td>
                            <td>
                                <span class="admin-badge {{ $book->processing_status === 'published' ? 'is-published' : ($book->processing_status === 'archived' ? 'is-archived' : ($book->processing_status === 'draft' ? 'is-draft' : '')) }}">
                                    {{ $book->processing_status ?? 'draft' }}
                                </span>
                            </td>
                            <td>
                                @if($book->is_featured)
                                    <span class="admin-badge is-featured">Featured</span>
                                @else
                                    <span class="admin-badge">No</span>
                                @endif
                            </td>
                            <td>{{ $book->published_at?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $book->created_at?->format('M d, Y') }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.books.show', $book) }}">View</a>
                                    <a href="{{ route('admin.books.edit', $book) }}">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="admin-empty-cell">No books match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $books->links() }}
        </div>
    </section>
@endsection
