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
                <article class="vocabulary-card">
                    <div class="vocabulary-card-top">
                        <span>{{ $entry->book?->title ?: 'Personal vocabulary' }}</span>
                        <form method="POST" action="{{ route('vocabulary.destroy', $entry) }}" onsubmit="return confirm('Remove this vocabulary item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Remove {{ $entry->original_text }}">×</button>
                        </form>
                    </div>
                    <div class="vocabulary-card-section">
                        <span>{{ str_contains(trim($entry->original_text), ' ') ? 'Phrase' : 'Word' }}</span>
                        <h2>{{ $entry->original_text }}</h2>
                    </div>
                    <div class="vocabulary-card-section vocabulary-card-translation">
                        <span>Translation</span>
                        <strong>{{ $entry->translated_text }}</strong>
                    </div>
                    @if($entry->context)
                        <div class="vocabulary-card-context">
                            <span>From the book</span>
                            <p>“{{ \Illuminate\Support\Str::limit($entry->context, 170) }}”</p>
                        </div>
                    @endif
                    <footer>
                        <small>{{ $entry->updated_at->diffForHumans() }}</small>
                        @if($entry->book)
                            <a href="{{ route('reader.show', ['book' => $entry->book, 'page' => $entry->reader_page, 'focus' => $entry->original_text]) }}">Open on page {{ $entry->reader_page }} →</a>
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
