@extends('layouts.app')

@section('title', 'Speaking practice')

@section('content')
    <section class="speaking-page-heading">
        <div>
            <span class="dashboard-date">Private pronunciation practice</span>
            <h1>Speak it out loud.</h1>
            <p>Listen, repeat, and compare what the browser heard.</p>
        </div>
        @if($entry)
            <span>{{ $position }} / {{ $totalEntries }}</span>
        @endif
    </section>

    @if(!$entry)
        <section class="speaking-empty">
            <div class="speaking-empty-icon">◉</div>
            <h2>Save a phrase before you practise it.</h2>
            <p>Add words or phrases to Vocabulary while reading. They will appear here automatically.</p>
            <a href="{{ route('library.index') }}">Open my library →</a>
        </section>
    @else
        <section
            class="speaking-practice"
            data-speaking-practice
            data-speaking-text="{{ $entry->original_text }}"
            data-speaking-locale="{{ $entry->book?->language_locale ?: 'en' }}"
        >
            <div class="speaking-session-card">
                <header>
                    <span>{{ str_contains(trim($entry->original_text), ' ') ? 'Phrase practice' : 'Word practice' }}</span>
                    <small>{{ $entry->book?->title ?: 'Personal vocabulary' }}</small>
                </header>

                <div class="speaking-prompt">
                    <button type="button" data-speaking-listen aria-label="Listen to pronunciation">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 10h4l5-4v12l-5-4H4v-4Z"></path>
                            <path d="M17 9c1.7 1.7 1.7 4.3 0 6M19.5 6.5c3 3 3 8 0 11"></path>
                        </svg>
                    </button>
                    <div>
                        <span>Say this in English</span>
                        <h2>{{ $entry->original_text }}</h2>
                        <p>{{ $entry->translated_text }}</p>
                    </div>
                </div>
                <p class="speaking-ai-disclosure">The pronunciation voice is AI-generated.</p>

                <div class="speaking-recorder">
                    <div class="speaking-wave" aria-hidden="true">
                        @for($i = 0; $i < 18; $i++) <i></i> @endfor
                    </div>
                    <button type="button" data-speaking-record>
                        <span class="speaking-record-icon"></span>
                        <strong>Start recording</strong>
                    </button>
                    <small data-speaking-support>Your voice stays in this browser.</small>
                </div>

                <div class="speaking-result" data-speaking-result hidden>
                    <div class="speaking-score"><strong data-speaking-score>0</strong><span>% match</span></div>
                    <div>
                        <span>The browser heard</span>
                        <p data-speaking-transcript></p>
                        <small data-speaking-feedback></small>
                    </div>
                </div>

                <footer>
                    <a href="{{ route('vocabulary.index') }}">← Vocabulary</a>
                    @if($nextEntry)
                        <a class="speaking-next" href="{{ route('speaking.index', ['entry' => $nextEntry->id]) }}">Next phrase <span>→</span></a>
                    @endif
                </footer>
            </div>

            <aside class="speaking-guide">
                <span class="section-kicker">Three small steps</span>
                <ol>
                    <li><i>1</i><div><strong>Listen</strong><p>Hear the phrase at a natural pace.</p></div></li>
                    <li><i>2</i><div><strong>Repeat</strong><p>Say it clearly without rushing.</p></div></li>
                    <li><i>3</i><div><strong>Compare</strong><p>See what the browser recognised.</p></div></li>
                </ol>
                <p class="speaking-privacy">Microphone access is requested only when you start recording.</p>
            </aside>
        </section>
    @endif
@endsection
