@extends('admin.layouts.app')

@section('title', $managedBook->title)
@section('eyebrow', 'Book details')

@section('content')
    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="admin-kicker">Metadata</span>
                    <h2>{{ $managedBook->title }}</h2>
                </div>
                <a class="admin-link-button" href="{{ route('admin.books.edit', $managedBook) }}">Edit book</a>
            </div>

            <dl class="admin-details">
                <div><dt>ID</dt><dd>#{{ $managedBook->id }}</dd></div>
                <div><dt>Slug</dt><dd>{{ $managedBook->slug ?? '—' }}</dd></div>
                <div><dt>Subtitle</dt><dd>{{ $managedBook->subtitle ?? '—' }}</dd></div>
                <div><dt>Short description</dt><dd>{{ $managedBook->short_description ?? '—' }}</dd></div>
                <div><dt>Author</dt><dd>{{ $managedBook->authorRelation?->name ?? $managedBook->author ?? '—' }}</dd></div>
                <div><dt>Category</dt><dd>{{ $managedBook->categoryRelation?->name ?? $managedBook->category ?? '—' }}</dd></div>
                <div><dt>Language</dt><dd>{{ $managedBook->languageRelation?->name ?? $managedBook->language_locale ?? '—' }}</dd></div>
                <div><dt>Difficulty</dt><dd>{{ $managedBook->difficulty ?? $managedBook->level ?? '—' }}</dd></div>
                <div><dt>Access type</dt><dd><span class="admin-badge {{ $managedBook->access_type === 'public' ? 'is-public' : '' }}">{{ $managedBook->access_type ?? 'private' }}</span></dd></div>
                <div><dt>Status</dt><dd><span class="admin-badge {{ $managedBook->processing_status === 'published' ? 'is-published' : ($managedBook->processing_status === 'archived' ? 'is-archived' : '') }}">{{ $managedBook->processing_status ?? 'draft' }}</span></dd></div>
                <div><dt>Featured</dt><dd>{{ $managedBook->is_featured ? 'Yes' : 'No' }}</dd></div>
                <div><dt>Total words</dt><dd>{{ number_format($managedBook->total_words ?? 0) }}</dd></div>
                <div><dt>Owner</dt><dd>{{ $managedBook->owner?->name ?? $managedBook->bookOwner?->name ?? '—' }}</dd></div>
                <div><dt>Created by</dt><dd>{{ $managedBook->createdBy?->name ?? '—' }}</dd></div>
                <div><dt>Published at</dt><dd>{{ $managedBook->published_at?->format('M d, Y H:i') ?? '—' }}</dd></div>
                <div><dt>Archived at</dt><dd>{{ $managedBook->archived_at?->format('M d, Y H:i') ?? '—' }}</dd></div>
                <div><dt>Created at</dt><dd>{{ $managedBook->created_at?->format('M d, Y H:i') }}</dd></div>
                <div><dt>Updated at</dt><dd>{{ $managedBook->updated_at?->format('M d, Y H:i') }}</dd></div>
                <div><dt>Related data</dt><dd>
                    @if($managedBook->reading_progress_count > 0 || $managedBook->dictionary_entries_count > 0)
                        {{ number_format($managedBook->reading_progress_count) }} readers,
                        {{ number_format($managedBook->dictionary_entries_count) }} dictionary entries
                    @else
                        No related data yet.
                    @endif
                </dd></div>
            </dl>
        </article>

        <aside class="admin-panel admin-action-panel">
            <div class="admin-panel-head">
                <span class="admin-kicker">Content</span>
                <h2>Book content</h2>
            </div>

            @if($managedBook->content)
                <p style="color:#596273;font-size:14px;margin:0 0 16px;">
                    {{ number_format(mb_strlen($managedBook->content)) }} characters of text are stored.
                </p>
                <a class="admin-link-button" href="{{ route('reader.show', $managedBook) }}" target="_blank" rel="noopener">Preview in reader →</a>
            @else
                <p style="color:#7b8499;font-size:14px;margin:0 0 16px;">
                    This book has no imported content yet.
                </p>
                <p style="color:#7b8499;font-size:13px;">Import TXT/EPUB content in the next stage.</p>
            @endif

            @if($managedBook->cover_path)
                <div style="margin-top:16px;">
                    <span class="admin-kicker">Cover</span>
                    <div style="margin-top:8px;">
                        <img src="{{ Storage::url($managedBook->cover_path) }}" alt="" style="max-width:160px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                    </div>
                </div>
            @endif

            <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(28,39,66,.07);">
                <span class="admin-kicker">Actions</span>

                @if($managedBook->processing_status !== 'published')
                    <form method="POST" action="{{ route('admin.books.publish', $managedBook) }}" style="margin-top:8px;" onsubmit="return confirm('Publish this book?')">
                        @csrf
                        <button class="admin-primary-button" type="submit">Publish</button>
                    </form>
                @endif

                @if($managedBook->processing_status === 'published')
                    <form method="POST" action="{{ route('admin.books.unpublish', $managedBook) }}" style="margin-top:8px;" onsubmit="return confirm('Unpublish this book? It will no longer appear in the public catalog.')">
                        @csrf
                        <button class="admin-muted-button" type="submit">Unpublish</button>
                    </form>
                @endif

                @if($managedBook->processing_status !== 'archived')
                    <form method="POST" action="{{ route('admin.books.archive', $managedBook) }}" style="margin-top:8px;" onsubmit="return confirm('Archive this book? It will be hidden from all listings.')">
                        @csrf
                        <button class="admin-danger-button" type="submit">Archive</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.books.restore', $managedBook) }}" style="margin-top:8px;" onsubmit="return confirm('Restore this book?')">
                        @csrf
                        <button class="admin-primary-button" type="submit">Restore</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.books.destroy', $managedBook) }}" style="margin-top:12px;" onsubmit="return confirm('Permanently delete this book? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button class="admin-danger-button" type="submit" style="background:transparent;border:1px solid #e74c3c;color:#e74c3c;">Delete book</button>
                </form>
            </div>

            <a class="admin-muted-link" href="{{ route('admin.books.index') }}" style="display:block;margin-top:16px;">← Back to books</a>
        </aside>
    </section>

    @if($managedBook->description)
        <section class="admin-panel" style="margin-top:20px;">
            <span class="admin-kicker">Description</span>
            <div style="margin-top:8px;line-height:1.7;color:#24304a;">
                {{ $managedBook->description }}
            </div>
        </section>
    @endif
@endsection
