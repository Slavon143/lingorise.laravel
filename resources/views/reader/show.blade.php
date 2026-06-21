<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · LingoRise Reader</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="reader-page" data-vocabulary-url="{{ route('vocabulary.store', $book) }}" data-translation-url="{{ route('reader.translate', $book) }}" data-native-language="{{ $nativeLanguage }}">
    <header class="reader-app-header">
        <a href="{{ route('library.index') }}" class="reader-back">← Library</a>
        <div class="reader-book-name">
            <strong>{{ $book->title }}</strong>
            <span>{{ $book->author ?: 'Personal text' }}</span>
        </div>
        <div class="reader-header-actions">
            <button class="reader-panels-button" type="button" data-reader-panels aria-label="Hide reading panels" title="Show or hide reading panels">
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <rect x="2.5" y="3" width="15" height="14" rx="2"></rect>
                    <path d="M7 3v14M13 3v14"></path>
                </svg>
                <span data-reader-panels-label>Panels</span>
            </button>
            <label class="reader-font-select">
                <span class="sr-only">Reading font</span>
                <select data-reader-font aria-label="Reading font">
                    <option value="kindle">Kindle style</option>
                    <option value="apple">Apple Books style</option>
                    <option value="google">Google Play Books</option>
                    <option value="readera" selected>ReadEra style</option>
                </select>
            </label>
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
                            <span
                                class="reader-token"
                                role="button"
                                tabindex="0"
                                data-reader-word="{{ trim($word, ".,!?;:()[]{}\"'“”‘’—–") }}"
                                data-word-index="{{ $wordIndex }}"
                            >{{ $word }}</span>
                            @php($wordIndex++)
                        @endforeach
                        </p>
                    @endif
                @endforeach
            </div>
        </article>

        <aside class="reader-side-panel">
            <div class="reader-side-heading">
                <span>Vocabulary <i data-vocabulary-count>{{ $savedEntries->count() }}</i></span>
                <strong>Save words as you read</strong>
            </div>
            <div class="reader-side-empty" data-vocabulary-empty @if($savedEntries->isNotEmpty()) hidden @endif>
                <span>Aa</span>
                <p>Select a word in the text to open its vocabulary card.</p>
            </div>
            <div class="reader-vocabulary-list" data-vocabulary-list>
                @foreach($savedEntries as $entry)
                    <button type="button" class="reader-vocabulary-item" data-vocabulary-original="{{ $entry->original_text }}">
                        <strong>{{ $entry->original_text }}</strong>
                        <span>{{ $entry->translated_text }}</span>
                    </button>
                @endforeach
            </div>
        </aside>
    </main>

    <button class="reader-panel-backdrop" type="button" data-reader-panel-backdrop aria-label="Close reading sidebar" hidden></button>

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
        <span class="word-card-arrow" aria-hidden="true"></span>
        <div class="word-card-head">
            <div class="word-card-title">
                <span data-selection-label>Selected word</span>
                <strong data-selected-word></strong>
            </div>
            <div class="word-card-head-actions">
                <button class="word-card-speak" type="button" data-speak-word aria-label="Hear pronunciation" title="Hear pronunciation">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M3 8h3l4-3.5v11L6 12H3V8Z"></path>
                        <path d="M13 7.2c1.5 1.5 1.5 4.1 0 5.6M15.2 5c2.8 2.8 2.8 7.2 0 10"></path>
                    </svg>
                </button>
                <button class="word-card-close" type="button" data-close-word-card aria-label="Close translation">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.5 5.5 14.5 14.5M14.5 5.5 5.5 14.5"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="word-card-pronunciation" data-word-pronunciation hidden></div>
        <div class="word-card-translation">
            <span data-native-label>Translation</span>
            <strong data-word-translation></strong>
        </div>
        <div class="word-card-explanation" data-word-explanation hidden>
            <p></p>
        </div>
        <p data-word-context hidden></p>
        <button class="word-card-save" type="button" data-save-word>
            <span>＋</span> Add to vocabulary
        </button>
        <small class="word-card-status" data-word-status></small>
    </div>
</body>
</html>
