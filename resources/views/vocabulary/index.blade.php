@extends('layouts.app')

@section('title', 'Vocabulary')

@section('content')
    <section class="vocabulary-heading">
        <div>
            <span class="dashboard-date">Words from your reading</span>
            <h1>Vocabulary</h1>
            <p>Review every word and phrase you saved while reading.</p>
        </div>
        <strong>{{ $entries->total() }} saved</strong>
    </section>

    @if(!auth()->user()->isPro() && $entries->total() >= 10)
        <div class="plan-nudge">
            <svg width="18" height="18" viewBox="0 0 22 22" fill="none"><path d="M11 2l2.5 5.5L19 8.5l-4 4 1 6L11 15l-5 3 1-6-4-4 5.5-1L11 2Z" fill="currentColor" stroke="currentColor" stroke-width="1.2"/></svg>
            <span>{{ 15 - $entries->total() > 0 ? (15 - $entries->total()) . ' more words before the limit — upgrade to Pro for unlimited.' : 'Free limit reached. <a href="' . route('pricing.index') . '">Upgrade to Pro</a> for unlimited vocabulary.' }}</span>
            <a href="{{ route('pricing.index') }}">View plans <svg width="14" height="14" viewBox="0 0 22 22" fill="none" style="vertical-align:-2px;margin-left:2px;"><path d="M5 11h12M11 5l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
    @endif

    <form class="vocabulary-toolbar" method="GET" action="{{ route('vocabulary.index') }}">
        <label>
            <span>⌕</span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search words or translations">
        </label>
        <select name="book" aria-label="Filter by book">
            <option value="">All books</option>
            @foreach($books as $book)
                <option value="{{ $book->id }}" @selected($selectedBook === $book->id)>{{ $book->title }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
        @if($search !== '' || $selectedBook)
            <a href="{{ route('vocabulary.index') }}">Clear</a>
        @endif
    </form>

    @if($entries->isEmpty())
        <section class="vocabulary-empty">
            <span>Aa</span>
            <h2>{{ $search !== '' || $selectedBook ? 'Nothing found.' : 'Your vocabulary starts in a book.' }}</h2>
            <p>{{ $search !== '' || $selectedBook ? 'Try a different word or book.' : 'Select a word or phrase while reading, then save it here.' }}</p>
            <a href="{{ route('library.index') }}">Open my library →</a>
        </section>
    @else
        <section class="vocabulary-grid">
            @foreach($entries as $entry)
                @php($isPhrase = str_contains(trim($entry->original_text), ' '))
                <article class="vocabulary-card {{ $isPhrase ? 'is-phrase' : 'is-word' }}">
                    <div class="vocabulary-card-top">
                        <span class="vocabulary-book-name">{{ $entry->book?->title ?: 'Personal vocabulary' }}</span>
                        <form method="POST" action="{{ route('vocabulary.destroy', $entry) }}" onsubmit="return confirm('Remove this vocabulary item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Remove {{ $entry->original_text }}">
                                <svg viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M6 6l8 8M14 6l-8 8"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    <div class="vocabulary-card-section">
                        <span class="vocabulary-type"><i>{{ $isPhrase ? 'P' : 'W' }}</i>{{ $isPhrase ? 'Phrase' : 'Word' }}</span>
                        <h2>{{ $entry->original_text }}</h2>
                    </div>
                    <div class="vocabulary-card-section vocabulary-card-translation">
                        <span><i>→</i> Translation</span>
                        <strong>{{ $entry->translated_text }}</strong>
                    </div>
                    @if($entry->context)
                        <div class="vocabulary-card-context">
                            <span><i>“</i> From the book</span>
                            <p>“{{ \Illuminate\Support\Str::limit($entry->context, 170) }}”</p>
                        </div>
                    @endif
                    <footer>
                        <small>{{ $entry->updated_at->diffForHumans() }}</small>
                        @if($entry->book)
                            <a href="{{ route('reader.show', ['book' => $entry->book, 'page' => $entry->reader_page, 'focus' => $entry->original_text]) }}">
                                <span>Open on page {{ $entry->reader_page }}</span>
                                <i>→</i>
                            </a>
                        @endif
                    </footer>
                </article>
            @endforeach
        </section>

        @if($entries->hasPages())
            <nav class="vocabulary-pagination">
                @if($entries->onFirstPage()) <span>← Previous</span> @else <a href="{{ $entries->previousPageUrl() }}">← Previous</a> @endif
                <small>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</small>
                @if($entries->hasMorePages()) <a href="{{ $entries->nextPageUrl() }}">Next →</a> @else <span>Next →</span> @endif
            </nav>
        @endif
    @endif
@endsection
