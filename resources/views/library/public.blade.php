@extends('layouts.app')

@section('title', 'Public library')

@section('content')
    <section class="library-app-heading">
        <div>
            <span class="dashboard-date">Community books</span>
            <h1>Public library</h1>
            <p>Discover books shared by other readers. Add any book to your personal library.</p>
        </div>
        <a href="{{ route('library.public') }}">Browse all</a>
    </section>

    @if(!auth()->user()->isPro())
        @php
            $publicBookCount = auth()->user()->books()->whereNotNull('original_book_id')->count();
        @endphp
        @if($publicBookCount >= 2)
            <div class="plan-nudge">
                <span>✦</span>
                <span>Free limit reached. Upgrade to Pro to add more books from the public library.</span>
                <a href="{{ route('pricing.index') }}">View plans →</a>
            </div>
        @else
            <div class="plan-nudge plan-nudge-soft">
                <span>✦</span>
                <span>{{ 2 - $publicBookCount }} of 2 free public books added. Upgrade to Pro for unlimited access.</span>
                <a href="{{ route('pricing.index') }}">View plans →</a>
            </div>
        @endif
    @endif

    <form class="public-library-filters" method="GET" action="{{ route('library.public') }}">
        <div class="public-search">
            <span>⌕</span>
            <input type="search" name="search" placeholder="Search by title, author, or category…" value="{{ $search ?? '' }}">
        </div>
        <select name="level" onchange="this.form.submit()">
            <option value="">All levels</option>
            @foreach(['A1','A2','B1','B2','C1','C2'] as $level)
                <option value="{{ $level }}" @selected($selectedLevel === $level)>{{ $level }}</option>
            @endforeach
        </select>
        <select name="language" onchange="this.form.submit()">
            <option value="">All languages</option>
            @foreach(['en'=>'English','de'=>'German','es'=>'Spanish','fr'=>'French','sv'=>'Swedish'] as $code => $label)
                <option value="{{ $code }}" @selected($selectedLanguage === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    @if($books->isEmpty())
        <section class="empty-library">
            <div class="empty-library-art"><span></span><span></span><span></span></div>
            <span class="section-kicker">Nothing here yet</span>
            <h2>No public books found.</h2>
            <p>Try a different search or check back later for new community books.</p>
            <a href="{{ route('library.public') }}">Clear filters <span>→</span></a>
        </section>
    @else
        <section class="user-books-grid">
            @foreach($books as $book)
                <article class="user-book-card">
                    <div class="user-book-cover cover-tone-{{ ($loop->index % 3) + 1 }}">
                        @if($book->cover_path)
                            <img class="user-book-cover-image" src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover of {{ $book->title }}">
                            <span>{{ strtoupper($book->language_locale) }}</span>
                            <div><small>{{ $book->author ?: 'Unknown' }}</small><strong>{{ $book->title }}</strong></div>
                        @else
                            <div class="generated-book-cover">
                                <div class="generated-cover-head">
                                    <span>{{ strtoupper($book->language_locale) }}</span>
                                    <small>{{ $book->level }}</small>
                                </div>
                                <div class="generated-cover-title">
                                    <small>{{ $book->author ?: 'Unknown' }}</small>
                                    <strong>{{ $book->title }}</strong>
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($book->content), 190) }}</p>
                                <em>First page preview</em>
                            </div>
                        @endif
                        <span class="public-badge">Public</span>
                    </div>
                    <div class="user-book-meta">
                        <div><span class="level-pill level-easy">{{ $book->level }}</span><span>{{ number_format($book->total_words) }} words</span></div>
                        <h2>{{ $book->title }}</h2>
                        <p>{{ $book->author ?: 'Unknown' }} · shared by {{ $book->owner?->name ?? 'deleted user' }}</p>
                        <div class="user-book-actions">
                            <form method="POST" action="{{ route('library.public.add', $book) }}">
                                @csrf
                                <button type="submit">＋ Add to my library</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        @if($books->hasPages())
            <div class="pagination-wrap">
                {{ $books->links() }}
            </div>
        @endif
    @endif
@endsection
