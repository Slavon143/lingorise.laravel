<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · LingoRise Reader</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="reader-page" data-vocabulary-url="{{ route('vocabulary.store', $book) }}" data-native-language="{{ $nativeLanguage }}">
    <header class="reader-app-header">
        <a href="{{ route('library.index') }}" class="reader-back">← Library</a>
        <div class="reader-book-name">
            <strong>{{ $book->title }}</strong>
            <span>{{ $book->author ?: 'Personal text' }}</span>
        </div>
        <div class="reader-header-actions">
            <button type="button" data-reader-decrease aria-label="Decrease text size">A−</button>
            <button type="button" data-reader-increase aria-label="Increase text size">A＋</button>
            <button type="button" data-reader-theme aria-label="Toggle reading theme">◐</button>
        </div>
    </header>

    <main class="reader-workspace">
        <aside class="reader-info-panel">
            <span class="section-kicker">Reading now</span>
            <div class="reader-mini-cover">
                @if($book->cover_path)
                    <img class="reader-mini-cover-image" src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover of {{ $book->title }}">
                    <span>{{ strtoupper($book->language_locale) }}</span>
                    <div><small>{{ $book->author ?: 'Personal text' }}</small><strong>{{ $book->title }}</strong></div>
                @else
                    <div class="reader-generated-cover">
                        <span>{{ strtoupper($book->language_locale) }}</span>
                        <small>{{ $book->author ?: 'Personal text' }}</small>
                        <strong>{{ $book->title }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($book->content), 105) }}</p>
                    </div>
                @endif
            </div>
            <div class="reader-book-stats">
                <div><span>Level</span><strong>{{ $book->level }}</strong></div>
                <div><span>Page</span><strong>{{ $page }} / {{ $totalPages }}</strong></div>
                <div><span>Reading time</span><strong>~{{ $readingTime }} min</strong></div>
            </div>
            <div class="reader-tip">
                <span>Tip</span>
                <p>Click any word to add its translation and save it with context.</p>
            </div>
        </aside>

        <article class="reading-paper" data-reading-paper>
            <div class="reading-paper-head">
                <span>Page {{ $page }}</span>
                <span>{{ $percentage }}% complete</span>
            </div>
            <div class="reading-text" data-reading-text>
                @php($wordIndex = 0)
                @foreach($pageBlocks as $block)
                    @if($block['type'] === 'heading')
                        <h2 class="reading-section-title">{{ $block['text'] }}</h2>
                    @else
                        <p>
                        @foreach(preg_split('/\s+/u', $block['text'], -1, PREG_SPLIT_NO_EMPTY) as $word)
                            <button
                                type="button"
                                class="reader-token"
                                data-reader-word="{{ trim($word, ".,!?;:()[]{}\"'“”‘’—–") }}"
                                data-word-index="{{ $wordIndex }}"
                            >{{ $word }}</button>
                            @php($wordIndex++)
                        @endforeach
                        </p>
                    @endif
                @endforeach
            </div>
        </article>

        <aside class="reader-side-panel">
            <div class="reader-side-heading">
                <span>Vocabulary</span>
                <strong>Save words as you read</strong>
            </div>
            <div class="reader-side-empty">
                <span>Aa</span>
                <p>Select a word in the text to open its vocabulary card.</p>
            </div>
        </aside>
    </main>

    <footer class="reader-pagination">
        @if($page > 1)
            <a href="{{ route('reader.show', ['book' => $book, 'page' => $page - 1]) }}">← Previous</a>
        @else
            <span></span>
        @endif
        <div>
            <span>{{ $percentage }}%</span>
            <div><i style="width: {{ $percentage }}%"></i></div>
            <small>Page {{ $page }} of {{ $totalPages }}</small>
        </div>
        @if($page < $totalPages)
            <a href="{{ route('reader.show', ['book' => $book, 'page' => $page + 1]) }}">Next →</a>
        @else
            <a href="{{ route('library.index') }}">Finish ✓</a>
        @endif
    </footer>

    <div class="word-card" data-word-card hidden>
        <span class="word-card-arrow"></span>
        <div class="word-card-head">
            <div><strong data-selected-word></strong><small data-native-label>Translation</small></div>
            <button type="button" data-close-word-card>×</button>
        </div>
        <label>
            <span>Translation</span>
            <input type="text" data-word-translation placeholder="Type the translation">
        </label>
        <p data-word-context></p>
        <button type="button" data-save-word>＋ Add to vocabulary</button>
        <small class="word-card-status" data-word-status></small>
    </div>
</body>
</html>
