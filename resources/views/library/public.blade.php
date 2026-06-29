@extends('layouts.app')

@section('title', 'Public library')

@section('content')
    <section class="library-app-heading">
        <div>
            <span class="dashboard-date">Community books</span>
            <h1>Public library</h1>
            <p>Discover books shared by other readers. Add any book to your personal library.</p>
        </div>
        <a href="{{ route('library.public') }}">
            <svg width="16" height="16" viewBox="0 0 22 22" fill="none" style="vertical-align:-3px;margin-right:6px;"><path d="M10 3H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16 3 19 6 12 13H9v-3l7-7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            Browse all
        </a>
    </section>

    @if(!auth()->user()->isPro())
        @php
            $publicBookCount = auth()->user()->books()->whereNotNull('original_book_id')->count();
        @endphp
        @if($publicBookCount >= 2)
            <div class="plan-nudge">
                <svg width="18" height="18" viewBox="0 0 22 22" fill="none"><path d="M11 2l2.5 5.5L19 8.5l-4 4 1 6L11 15l-5 3 1-6-4-4 5.5-1L11 2Z" fill="currentColor" stroke="currentColor" stroke-width="1.2"/></svg>
                <span>Free limit reached. Upgrade to Pro to add more books from the public library.</span>
                <a href="{{ route('pricing.index') }}">View plans <svg width="14" height="14" viewBox="0 0 22 22" fill="none" style="vertical-align:-2px;margin-left:2px;"><path d="M5 11h12M11 5l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
        @else
            <div class="plan-nudge plan-nudge-soft">
                <svg width="18" height="18" viewBox="0 0 22 22" fill="none"><path d="M11 2l2.5 5.5L19 8.5l-4 4 1 6L11 15l-5 3 1-6-4-4 5.5-1L11 2Z" fill="currentColor" stroke="currentColor" stroke-width="1.2"/></svg>
                <span>{{ 2 - $publicBookCount }} of 2 free public books added. Upgrade to Pro for unlimited access.</span>
                <a href="{{ route('pricing.index') }}">View plans <svg width="14" height="14" viewBox="0 0 22 22" fill="none" style="vertical-align:-2px;margin-left:2px;"><path d="M5 11h12M11 5l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
        @endif
    @endif

    <form class="public-library-filters" method="GET" action="{{ route('library.public') }}">
        <div class="public-search">
            <svg width="18" height="18" viewBox="0 0 22 22" fill="none" style="flex-shrink:0;"><circle cx="9.5" cy="9.5" r="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 14 20 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
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
            <a href="{{ route('library.public') }}">Clear filters <svg width="14" height="14" viewBox="0 0 22 22" fill="none" style="vertical-align:-2px;margin-left:4px;"><path d="M5 11h12M11 5l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </section>
    @else
        <section class="user-books-grid">
            @foreach($books as $book)
                <article class="user-book-card">
                    <div class="user-book-cover cover-tone-{{ ($loop->index % 3) + 1 }}">
                        @if($book->cover_path)
                            <img class="user-book-cover-image" src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover of {{ $book->title }}">
                            <span class="public-badge">Public</span>
                            <div class="user-book-cover-overlay">
                                <div class="user-book-cover-top">
                                    <span class="cover-lang-badge">{{ strtoupper($book->language_locale) }}</span>
                                </div>
                                <div class="cover-bottom">
                                    <strong>{{ $book->title }}</strong>
                                    <small>{{ $book->author ?: 'Unknown' }}</small>
                                </div>
                            </div>
                        @else
                            <div class="generated-book-cover">
                                <div class="generated-cover-head">
                                    <span>{{ strtoupper($book->language_locale) }}</span>
                                </div>
                                <div class="generated-cover-title">
                                    <strong>{{ $book->title }}</strong>
                                    <small>{{ $book->author ?: 'Unknown' }}</small>
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($book->content), 190) }}</p>
                                <em>First page preview</em>
                            </div>
                            <span class="public-badge">Public</span>
                        @endif
                    </div>
                    <div class="user-book-meta">
                        <div class="user-book-meta-top">
                            <span class="level-pill">{{ $book->level }}</span>
                            <span class="word-count">{{ number_format($book->total_words) }} words</span>
                        </div>
                        <h2>{{ $book->title }}</h2>
                        <p>{{ $book->author ?: 'Unknown' }} · {{ $book->owner?->name ?? 'deleted user' }}</p>
                        <div class="user-book-actions">
                            <form method="POST" action="{{ route('library.public.add', $book) }}" style="width:100%;">
                                @csrf
                                <button type="submit" class="btn-add" style="width:100%;">
                                    <svg viewBox="0 0 22 22"><path d="M5 11h12M11 5v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    Add to my library
                                </button>
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
