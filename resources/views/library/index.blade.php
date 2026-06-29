@extends('layouts.app')

@section('title', 'My library')

@section('content')
    <section class="library-app-heading">
        <div>
            <span class="dashboard-date">Your reading space</span>
            <h1>My library</h1>
            <p>Read your own books and keep every new word in context.</p>
        </div>
        <a href="{{ route('library.public') }}">
            <svg width="16" height="16" viewBox="0 0 22 22" fill="none" style="vertical-align:-3px;margin-right:6px;"><path d="M3 11h16M7 4v7M11 4v7M15 4v7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="3" y="11" width="16" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
            Browse public library
        </a>
    </section>

    <form class="public-library-filters" method="GET" action="{{ route('library.index') }}" style="margin-bottom:24px;">
        <div class="public-search">
            <svg width="18" height="18" viewBox="0 0 22 22" fill="none" style="flex-shrink:0;"><circle cx="9.5" cy="9.5" r="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 14 20 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <input type="search" name="search" placeholder="Search your books by title, author, or category…" value="{{ $search ?? '' }}">
        </div>
        <button type="submit">Search</button>
    </form>

    @if($books->isEmpty())
        <section class="empty-library">
            <div class="empty-library-art">
                <span></span><span></span><span></span>
            </div>
            <span class="section-kicker">Your shelf is waiting</span>
            <h2>Add the first story<br>you want to understand.</h2>
            <p>Paste text or upload a TXT or EPUB file. LingoRise will prepare it for focused reading.</p>
            <a href="{{ route('library.create') }}">
                Add your first book
                <svg width="16" height="16" viewBox="0 0 22 22" fill="none" style="vertical-align:-3px;margin-left:6px;"><path d="M5 11h12M11 5v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </a>
        </section>
    @else
        <section class="user-books-grid">
            @foreach($books as $book)
                <article class="user-book-card">
                    <div class="user-book-cover cover-tone-{{ ($loop->index % 3) + 1 }}">
                        @if($book->cover_path)
                            <img class="user-book-cover-image" src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover of {{ $book->title }}">
                            @if($book->isPublic())
                                <span class="public-badge">Public</span>
                            @endif
                            <div class="user-book-cover-overlay">
                                <div class="user-book-cover-top">
                                    <span class="cover-lang-badge">{{ strtoupper($book->language_locale) }}</span>
                                </div>
                                <div class="cover-bottom">
                                    <strong>{{ $book->title }}</strong>
                                    @if($book->author)
                                        <small>{{ $book->author }}</small>
                                    @else
                                        <small class="cover-status-chip">Personal text</small>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="generated-book-cover">
                                <div class="generated-cover-head">
                                    <span>{{ strtoupper($book->language_locale) }}</span>
                                </div>
                                <div class="generated-cover-title">
                                    <strong>{{ $book->title }}</strong>
                                    @if($book->author)
                                        <small>{{ $book->author }}</small>
                                    @else
                                        <small class="cover-status-chip">Personal text</small>
                                    @endif
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($book->content), 190) }}</p>
                                <em>First page preview</em>
                            </div>
                            @if($book->isPublic())
                                <span class="public-badge">Public</span>
                            @endif
                        @endif
                    </div>
                    <div class="user-book-meta">
                        <div class="user-book-meta-top">
                            <span class="level-pill">{{ $book->level }}</span>
                            <span class="word-count">{{ number_format($book->total_words) }} words</span>
                        </div>
                        <h2>{{ $book->title }}</h2>
                        <p>{{ $book->author ?: 'Added by you' }}</p>
                        <div class="user-book-actions">
                            <a href="{{ route('reader.show', $book) }}" class="btn-read">
                                <svg viewBox="0 0 20 20"><path d="M5 4h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><path d="M8 7 13 10 8 13Z"/></svg>
                                Read
                            </a>
                            <div class="user-book-actions-right">
                                <form method="POST" action="{{ route('library.visibility', $book) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-vis {{ $book->isPublic() ? 'is-public' : '' }}">
                                        <svg width="14" height="14" viewBox="0 0 22 22" fill="none"><path d="M11 5C7 5 3.5 8 2 11c1.5 3 5 6 9 6s7.5-3 9-6c-1.5-3-5-6-9-6Z" stroke="currentColor" stroke-width="1.6"/><circle cx="11" cy="11" r="3" stroke="currentColor" stroke-width="1.6"/></svg>
                                        {{ $book->isPublic() ? 'Public' : 'Private' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('library.destroy', $book) }}" onsubmit="return confirm('Remove this book?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-del">
                                        <svg width="14" height="14" viewBox="0 0 22 22" fill="none"><path d="M4 6h14M9 6V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2M18 6l-1 12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
            <a class="add-book-tile" href="{{ route('library.create') }}">
                <span>
                    <svg width="24" height="24" viewBox="0 0 22 22" fill="none"><path d="M5 11h12M11 5v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <strong>Add another book</strong>
                <small>TXT, EPUB, or pasted text</small>
            </a>
        </section>
    @endif
@endsection
