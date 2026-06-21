@extends('layouts.app')

@section('title', 'My library')

@section('content')
    <section class="library-app-heading">
        <div>
            <span class="dashboard-date">Your reading space</span>
            <h1>My library</h1>
            <p>Read your own books and keep every new word in context.</p>
        </div>
        <a href="{{ route('library.public') }}">Browse public library</a>
    </section>

    <form class="public-library-filters" method="GET" action="{{ route('library.index') }}" style="margin-bottom:24px;">
        <div class="public-search">
            <span>⌕</span>
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
            <a href="{{ route('library.create') }}">Add your first book <span>→</span></a>
        </section>
    @else
        <section class="user-books-grid">
            @foreach($books as $book)
                <article class="user-book-card">
                    <div class="user-book-cover cover-tone-{{ ($loop->index % 3) + 1 }}">
                        @if($book->cover_path)
                            <img class="user-book-cover-image" src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover of {{ $book->title }}">
                            <span>{{ strtoupper($book->language_locale) }}</span>
                            <div><small>{{ $book->author ?: 'Personal text' }}</small><strong>{{ $book->title }}</strong></div>
                        @else
                            <div class="generated-book-cover">
                                <div class="generated-cover-head">
                                    <span>{{ strtoupper($book->language_locale) }}</span>
                                    <small>{{ $book->level }}</small>
                                </div>
                                <div class="generated-cover-title">
                                    <small>{{ $book->author ?: 'Personal text' }}</small>
                                    <strong>{{ $book->title }}</strong>
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($book->content), 190) }}</p>
                                <em>First page preview</em>
                            </div>
                        @endif
                        @if($book->isPublic())
                            <span class="public-badge">Public</span>
                        @endif
                    </div>
                        <div class="user-book-meta">
                            <div><span class="level-pill level-easy">{{ $book->level }}</span><span>{{ number_format($book->total_words) }} words</span></div>
                            <h2>{{ $book->title }}</h2>
                            <p>{{ $book->author ?: 'Added by you' }}</p>
                            <div class="user-book-actions">
                                <a href="{{ route('reader.show', $book) }}">Start reading</a>
                                <div class="user-book-actions-right">
                                    <form method="POST" action="{{ route('library.visibility', $book) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="vis-toggle">{{ $book->isPublic() ? 'Make private' : 'Make public' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('library.destroy', $book) }}" onsubmit="return confirm('Remove this book?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                </article>
            @endforeach
            <a class="add-book-tile" href="{{ route('library.create') }}"><span>＋</span><strong>Add another book</strong><small>TXT, EPUB, or pasted text</small></a>
        </section>
    @endif
@endsection
