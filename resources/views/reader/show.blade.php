<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · LingoRise Reader</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="reader-page" data-vocabulary-url="{{ route('vocabulary.store', $book) }}" data-translation-url="{{ route('reader.translate', $book) }}" data-context-explain-url="{{ route('reader.context-explain', $book) }}" data-grammar-explain-url="{{ route('reader.grammar-explain', $book) }}" data-simplify-url="{{ route('reader.simplify', $book) }}" data-shadowing-url="{{ route('reader.shadowing', $book) }}" data-speech-url="{{ route('speech.create') }}" data-native-language="{{ $nativeLanguage }}" data-book-language="{{ $book->language_locale }}" data-page-number="{{ $page }}" data-focus-phrase="{{ $focusPhrase }}">
    <header class="reader-app-header">
        <a href="{{ route('library.index') }}" class="reader-back">
            <span aria-hidden="true">←</span>
            <strong>Library</strong>
        </a>
        <div class="reader-book-name">
            <strong>{{ $book->title }}</strong>
            <span>{{ $book->author ?: 'Personal text' }}</span>
        </div>
        <button class="reader-panels-button" type="button" data-reader-panels aria-label="Open reading panel" title="Open reading panel">
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <rect x="2.5" y="3" width="15" height="14" rx="2"></rect>
                <path d="M7 3v14M13 3v14"></path>
            </svg>
            <span data-reader-panels-label>Panel</span>
        </button>
    </header>

    <main class="reader-workspace">
        <aside class="reader-info-panel">
            <div class="reader-panel-controls">
                <span class="reader-panel-control-label">Reading settings</span>
                <label class="reader-font-select">
                    <span class="sr-only">Reading font</span>
                    <select data-reader-font aria-label="Reading font">
                        <option value="kindle">Kindle style</option>
                        <option value="apple">Apple Books style</option>
                        <option value="google">Google Play Books</option>
                        <option value="readera" selected>ReadEra style</option>
                    </select>
                </label>
                <div class="reader-panel-control-grid">
                    <button type="button" data-reader-decrease aria-label="Decrease text size">A−</button>
                    <button type="button" data-reader-increase aria-label="Increase text size">A＋</button>
                    <button type="button" data-reader-theme aria-label="Toggle reading theme">◐</button>
                </div>
            </div>

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
                <div><span>Time remaining</span><strong>~{{ $readingTime }} min</strong></div>
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
            <a class="reader-page-nav reader-page-nav-prev" href="{{ route('reader.show', ['book' => $book, 'page' => $page - 1]) }}"><span>←</span> Previous</a>
        @else
            <span class="reader-page-nav reader-page-nav-prev is-disabled"><span>←</span> Previous</span>
        @endif
        <div class="reader-progress-panel">
            <div class="reader-progress-meta">
                <span>Page {{ $page }} of {{ $totalPages }}</span>
                <strong>{{ $percentage }}%</strong>
            </div>
            <div class="reader-progress-track" aria-label="{{ $percentage }}% complete"><i style="width: {{ $percentage }}%"></i></div>
            <form class="page-jump" action="{{ route('reader.show', $book) }}" method="GET" onsubmit="return jumpToPage(this)">
                <label for="jump-input" class="sr-only">Jump to page</label>
                <input id="jump-input" type="number" min="1" max="{{ $totalPages }}" placeholder="{{ $page }}/{{ $totalPages }}" required>
                <button type="submit">Go</button>
            </form>
        </div>
        @if($page < $totalPages)
            <a class="reader-page-nav reader-page-nav-next" href="{{ route('reader.show', ['book' => $book, 'page' => $page + 1]) }}">Next <span>→</span></a>
        @else
            <a class="reader-page-nav reader-page-nav-next" href="{{ route('library.index') }}">Finish <span>✓</span></a>
        @endif
    </footer>

    <?php
        $_i18n = [
            'selected_word' => __('reader.selected_word'),
            'translation' => __('reader.translation'),
            'save_to_vocabulary' => __('reader.save_to_vocabulary'),
            'saving' => __('reader.saving'),
            'saved' => __('reader.saved'),
            'study_tools' => __('reader.study_tools'),
            'context' => __('reader.context'),
            'grammar' => __('reader.grammar'),
            'simplify' => __('reader.simplify'),
            'practice_pronunciation' => __('reader.practice_pronunciation'),
            'close' => __('reader.close'),
            'listen' => __('reader.listen'),
            'context.meaning' => __('reader.context.meaning'),
            'context.why' => __('reader.context.why'),
            'context.role' => __('reader.context.role'),
            'context.fixed_expression' => __('reader.context.fixed_expression'),
            'context.literal_warning' => __('reader.context.literal_warning'),
            'context.register' => __('reader.context.register'),
            'context.connotation' => __('reader.context.connotation'),
            'context.synonyms' => __('reader.context.synonyms'),
            'context.common_mistake' => __('reader.context.common_mistake'),
            'context.example' => __('reader.context.example'),
            'context.level' => __('reader.context.level'),
            'context.base_form' => __('reader.context.base_form'),
            'context.tags' => __('reader.context.tags'),
            'context.loading_title' => __('reader.context.loading_title'),
            'context.loading_subtitle' => __('reader.context.loading_subtitle'),
            'grammar.title' => __('reader.grammar.title'),
            'grammar.parts' => __('reader.grammar.parts'),
            'grammar.loading_title' => __('reader.grammar.loading_title'),
            'grammar.loading_subtitle' => __('reader.grammar.loading_subtitle'),
            'simplify.title' => __('reader.simplify.title'),
            'simplify.original' => __('reader.simplify.original'),
            'simplify.simplified' => __('reader.simplify.simplified'),
            'simplify.loading_title' => __('reader.simplify.loading_title'),
            'simplify.loading_subtitle' => __('reader.simplify.loading_subtitle'),
            'error.title' => __('reader.error.title'),
            'error.subtitle' => __('reader.error.subtitle'),
            'error.try_again' => __('reader.error.try_again'),
        ];
    ?>
    <div class="word-card-overlay" data-word-card-overlay hidden></div>
    <div class="word-card" data-word-card hidden role="dialog" aria-modal="true" aria-labelledby="word-card-title"
         data-i18n='@json($_i18n)'>
        <div class="word-card-mobile-handle" aria-hidden="true"></div>
        <span class="word-card-arrow" aria-hidden="true"></span>
        <div class="word-card-head">
            <div class="word-card-title" id="word-card-title">
                <span data-selection-label>{{ __('reader.selected_word') }}</span>
                <strong class="selected-text" data-selected-word></strong>
            </div>
            <div class="word-card-head-actions">
                <button class="word-card-speak" type="button" data-speak-word aria-label="{{ __('reader.listen') }}" title="{{ __('reader.listen') }}">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M3 8h3l4-3.5v11L6 12H3V8Z"></path>
                        <path d="M13 7.2c1.5 1.5 1.5 4.1 0 5.6M15.2 5c2.8 2.8 2.8 7.2 0 10"></path>
                    </svg>
                </button>
                <button class="word-card-close" type="button" data-close-word-card aria-label="{{ __('reader.close') }}">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.5 5.5 14.5 14.5M14.5 5.5 5.5 14.5"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="word-card-pronunciation" data-word-pronunciation hidden></div>
        <div class="word-card-translation">
            <span data-native-label>{{ __('reader.translation') }}</span>
            <strong data-word-translation></strong>
        </div>
        <div class="word-card-explanation" data-word-explanation hidden><p></p></div>
        <span data-word-context hidden></span>
        <button class="word-card-save" type="button" data-save-word>{{ __('reader.save_to_vocabulary') }}</button>
        <small class="word-card-status" data-word-status></small>
        <div class="word-card-study-tools" data-study-tools hidden>
            <span class="word-card-study-tools-label">{{ __('reader.study_tools') }}</span>
            <div class="word-card-study-toolbar" role="tablist" data-tablist>
                <button type="button" class="word-card-study-btn" role="tab" aria-selected="false" data-ai-tool="context-explain" data-tab="context" disabled>{{ __('reader.context') }}</button>
                <button type="button" class="word-card-study-btn" role="tab" aria-selected="false" data-ai-tool="grammar-explain" data-tab="grammar" disabled>{{ __('reader.grammar') }}</button>
                <button type="button" class="word-card-study-btn" role="tab" aria-selected="false" data-ai-tool="simplify" data-tab="simplify" disabled>{{ __('reader.simplify') }}</button>
            </div>
            <div class="word-card-tab-panels">
                <div class="word-card-tab-content" role="tabpanel" data-tab-panel="context" hidden aria-live="polite"></div>
                <div class="word-card-tab-content" role="tabpanel" data-tab-panel="grammar" hidden aria-live="polite"></div>
                <div class="word-card-tab-content" role="tabpanel" data-tab-panel="simplify" hidden aria-live="polite">
                    <div class="simplify-levels" data-simplify-levels hidden>
                        <button type="button" class="simplify-level-btn" data-level="A1">A1</button>
                        <button type="button" class="simplify-level-btn" data-level="A2">A2</button>
                        <button type="button" class="simplify-level-btn is-active" data-level="B1">B1</button>
                        <button type="button" class="simplify-level-btn" data-level="B2">B2</button>
                        <button type="button" class="simplify-level-btn" data-level="C1">C1</button>
                    </div>
                    <div class="simplify-result" data-simplify-result></div>
                </div>
            </div>
        </div>
        <button type="button" class="word-card-practice-btn" data-practice-btn disabled>{{ __('reader.practice_pronunciation') }}</button>
        <div class="word-card-shadowing" data-shadowing-mode hidden>
            <div class="shadowing-head">
                <strong>{{ __('reader.practice_pronunciation') }}</strong>
            </div>
            <div class="shadowing-phrase" data-shadowing-phrase></div>
            <div class="shadowing-bar">
                <button type="button" class="shadowing-record-btn" data-shadowing-record disabled>
                    <span class="shadowing-record-icon">●</span>
                    <strong>Record</strong>
                </button>
                <button type="button" class="shadowing-play-btn" data-shadowing-play disabled hidden>Play my recording</button>
                <button type="button" class="shadowing-listen-btn" data-shadowing-listen>Listen to example</button>
            </div>
            <div class="shadowing-rating" data-shadowing-rating hidden>
                <span>How was it?</span>
                <button type="button" data-shadowing-rate="easy">Easy</button>
                <button type="button" data-shadowing-rate="okay">Okay</button>
                <button type="button" data-shadowing-rate="difficult">Difficult</button>
            </div>
            <button type="button" class="shadowing-back-btn" data-shadowing-back>Back to translation</button>
        </div>
        @if(!auth()->user()->isPro())
            <a href="{{ route('pricing.index') }}" class="word-card-upgrade" data-upgrade-btn hidden>
                <span>✦</span> Upgrade to Pro
            </a>
        @endif
    </div>
    <script>
    function jumpToPage(form) {
        const input = form.querySelector('#jump-input');
        const page = parseInt(input.value, 10);
        if (page < 1 || page > {{ $totalPages }}) return false;
        window.location.href = form.action + '?page=' + page;
        return false;
    }
    </script>
</body>
</html>
