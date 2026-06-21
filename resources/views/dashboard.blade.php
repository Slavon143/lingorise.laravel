<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Your LingoRise learning dashboard.">
    <title>Dashboard · LingoRise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="brand app-brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/>
                        <path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/>
                    </svg>
                </span>
                <span>Lingo<span>Rise</span></span>
            </a>
            <button class="sidebar-collapse-button" type="button" data-sidebar-collapse aria-label="Collapse sidebar" title="Collapse sidebar">
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <rect x="2.5" y="3" width="15" height="14" rx="2"></rect>
                    <path d="M7 3v14M12.5 7.25 10 10l2.5 2.75"></path>
                </svg>
                <span>Collapse</span>
            </button>

            <nav class="app-nav" aria-label="Dashboard navigation">
                <a class="is-active" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M4 10.5 11 4l7 6.5V18a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    <span>Home</span>
                </a>
                <a href="{{ route('library.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 4h10a2 2 0 0 1 2 2v12H7a2 2 0 0 1-2-2V4Zm0 10h12M8 7h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>My library</span>
                </a>
                <a href="{{ route('vocabulary.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Vocabulary</span>
                    <small>{{ auth()->user()->dictionaryEntries()->count() }}</small>
                </a>
                <a href="{{ route('speaking.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><rect x="7" y="3" width="8" height="12" rx="4" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 11.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5M11 18v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Speaking</span>
                </a>
                <a href="#">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M4 18V9m5 9V4m5 14v-6m5 6V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Progress</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <button class="language-summary" type="button" data-open-languages>
                    <span class="language-flag">{{ $preference?->learning_locale === 'de' ? 'DE' : 'EN' }}</span>
                    <span>
                        <small>Learning</small>
                        <strong>{{ $preference?->learning_locale === 'de' ? 'German' : 'English' }}</strong>
                    </span>
                    <span>⌄</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sidebar-logout" type="submit">
                        <svg viewBox="0 0 22 22" fill="none"><path d="M9 4H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4m5-3 4-4-4-4m4 4H8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Log out
                    </button>
                </form>
            </div>
        </aside>
        <button class="sidebar-mobile-backdrop" type="button" data-sidebar-backdrop aria-label="Close navigation" hidden></button>

        <main class="app-main">
            <header class="app-topbar">
                <button class="mobile-menu-button" type="button" aria-label="Open navigation">
                    <span></span><span></span><span></span>
                </button>
                <div class="topbar-search">
                    <svg viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="m13.5 13.5 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <input type="search" placeholder="Search your library">
                    <kbd>⌘ K</kbd>
                </div>
                <div class="topbar-actions">
                    <button class="notification-button" type="button" aria-label="Notifications">
                        <svg viewBox="0 0 22 22" fill="none"><path d="M6 9a5 5 0 0 1 10 0v4l2 3H4l2-3V9Zm3 9h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="user-button" type="button">
                        <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        <div><strong>{{ $user->name }}</strong><small>Free plan</small></div>
                    </button>
                </div>
            </header>

            <div class="dashboard-content">
                @if (session('status'))
                    <div class="dashboard-alert">{{ session('status') }}</div>
                @endif

                <section class="dashboard-welcome">
                    <div>
                        <span class="dashboard-date">{{ now()->format('l, F j') }}</span>
                        <h1>Good to see you, {{ explode(' ', $user->name)[0] }}.</h1>
                        <p>{{ $preference ? 'Ready for another small step in your English?' : 'Let’s personalise your learning journey first.' }}</p>
                    </div>
                    <div class="streak-summary">
                        <span>◆</span>
                        <div><strong>1 day</strong><small>Current streak</small></div>
                    </div>
                </section>

                @unless ($preference)
                    <section class="onboarding-card onboarding-card-expanded" id="language-setup">
                        <div class="onboarding-copy">
                            <span class="section-kicker">First things first</span>
                            <h2>Which language do you<br>want to bring to life?</h2>
                            <p>We’ll use your native language for translations and explanations. The app interface stays in English.</p>
                            <div class="onboarding-note">
                                <span>i</span>
                                <p>You can change both languages at any time.</p>
                            </div>
                        </div>
                        <form class="language-choice-form" method="POST" action="{{ route('settings.languages') }}">
                            @csrf
                            @method('PUT')

                            <fieldset class="language-choice-group">
                                <legend><span>1</span> I speak</legend>
                                <div class="language-options" data-language-group>
                                    @foreach ([
                                        'de' => ['DE', 'German', 'Deutsch'],
                                        'ru' => ['RU', 'Russian', 'Русский'],
                                        'sv' => ['SV', 'Swedish', 'Svenska'],
                                        'es' => ['ES', 'Spanish', 'Español'],
                                        'fr' => ['FR', 'French', 'Français'],
                                        'uk' => ['UK', 'Ukrainian', 'Українська'],
                                    ] as $code => [$flag, $name, $nativeName])
                                        <label class="language-option @if(old('native_locale', 'de') === $code) is-selected @endif">
                                            <input type="radio" name="native_locale" value="{{ $code }}" @checked(old('native_locale', 'de') === $code) required>
                                            <span class="option-flag">{{ $flag }}</span>
                                            <span><strong>{{ $name }}</strong><small>{{ $nativeName }}</small></span>
                                            <i>✓</i>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div class="language-direction" aria-hidden="true">
                                <span></span><strong>→</strong><span></span>
                            </div>

                            <fieldset class="language-choice-group">
                                <legend><span>2</span> I want to learn</legend>
                                <div class="language-options language-options-learning" data-language-group>
                                    @foreach ([
                                        'en' => ['EN', 'English', 'English'],
                                        'de' => ['DE', 'German', 'Deutsch'],
                                        'es' => ['ES', 'Spanish', 'Español'],
                                        'fr' => ['FR', 'French', 'Français'],
                                        'sv' => ['SV', 'Swedish', 'Svenska'],
                                    ] as $code => [$flag, $name, $nativeName])
                                        <label class="language-option @if(old('learning_locale', 'en') === $code) is-selected @endif">
                                            <input type="radio" name="learning_locale" value="{{ $code }}" @checked(old('learning_locale', 'en') === $code) required>
                                            <span class="option-flag">{{ $flag }}</span>
                                            <span><strong>{{ $name }}</strong><small>{{ $nativeName }}</small></span>
                                            <i>✓</i>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div class="language-choice-footer">
                                <p>Your explanations will appear in <strong data-native-summary>German</strong> while you read in <strong data-learning-summary>English</strong>.</p>
                                <button type="submit">Save and start learning <span>→</span></button>
                            </div>
                        </form>
                        @error('native_locale') <small class="onboarding-error">{{ $message }}</small> @enderror
                        @error('learning_locale') <small class="onboarding-error">{{ $message }}</small> @enderror
                    </section>
                @endunless

                <section class="dashboard-grid">
                    @if($continueBook)
                        <article class="continue-card">
                            <div class="card-heading">
                                <div><span>Continue reading</span><h2>{{ $continueBook->title }}</h2></div>
                                <span class="level-pill level-easy">{{ $continueBook->level }}</span>
                            </div>
                            <div class="continue-body">
                                <div class="continue-cover @if(!$continueBook->cover_path) cover-tone-{{ ($continueBook->id % 3) + 1 }} @endif">
                                    @if($continueBook->cover_path)
                                        <img class="user-book-cover-image" src="{{ asset('storage/'.$continueBook->cover_path) }}" alt="Cover of {{ $continueBook->title }}">
                                        <span style="position:relative;z-index:2;align-self:flex-start;">{{ strtoupper($continueBook->language_locale) }}</span>
                                        <div style="position:relative;z-index:2;"><small>{{ $continueBook->author ?: 'Personal text' }}</small><strong style="display:block;font-family:Georgia,serif;font-size:21px;font-weight:500;line-height:1;">{{ $continueBook->title }}</strong></div>
                                    @else
                                        <div class="generated-book-cover" style="inset:8px;">
                                            <div class="generated-cover-head">
                                                <span>{{ strtoupper($continueBook->language_locale) }}</span>
                                                <small>{{ $continueBook->level }}</small>
                                            </div>
                                            <div class="generated-cover-title">
                                                <small>{{ $continueBook->author ?: 'Personal text' }}</small>
                                                <strong>{{ $continueBook->title }}</strong>
                                            </div>
                                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($continueBook->content), 180) }}</p>
                                            <em>First page preview</em>
                                        </div>
                                    @endif
                                </div>
                                <div class="continue-details">
                                    <p>“{{ \Illuminate\Support\Str::limit(strip_tags($continueBook->content), 120) }}”</p>
                                    <div class="reading-progress">
                                        <div><span>Reading progress</span><strong>{{ $continuePercentage }}%</strong></div>
                                        <div class="dashboard-progress"><i style="width: {{ $continuePercentage }}%"></i></div>
                                        <small>Page {{ $continuePage }} of {{ $continueTotalPages }} @if($continueReadingTime)· {{ $continueReadingTime }} min left @endif</small>
                                    </div>
                                    <a href="{{ route('reader.show', ['book' => $continueBook, 'page' => $continuePage]) }}">Continue reading <span>→</span></a>
                                </div>
                            </div>
                        </article>
                    @else
                        <article class="continue-card" style="grid-column: 1 / 3;">
                            <div class="card-heading">
                                <div><span>Continue reading</span><h2>Start your first book</h2></div>
                            </div>
                            <div class="continue-body" style="display:flex; align-items:center; justify-content:center; min-height:160px;">
                                <p style="color:var(--muted); font-size:14px;">Add a book to your library to begin reading.</p>
                            </div>
                        </article>
                    @endif

                    <article class="daily-goal-card">
                        <div class="card-heading"><div><span>Daily goal</span><h2>Keep it light.</h2></div></div>
                        <div class="goal-ring">
                            <div><strong>{{ $dailyMinutes }}</strong><span>/ {{ $dailyGoal }} min</span><small>today</small></div>
                        </div>
                        <p>{{ $dailyMinutes >= $dailyGoal ? 'Great job! You hit your daily goal.' : ($dailyGoal - $dailyMinutes . ' more ' . Str::plural('minute', $dailyGoal - $dailyMinutes) . ' will keep your streak alive.') }}</p>
                    </article>

                    <article class="stat-card">
                        <span class="stat-icon stat-icon-blue">
                            <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Words saved</small><strong>{{ $recentEntries }}</strong><span>{{ $recentEntries > 0 ? 'Kept in your vocabulary' : 'Your vocabulary starts here' }}</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-icon-green">
                            <svg viewBox="0 0 22 22" fill="none"><path d="M4 18V9m5 9V4m5 14v-6m5 6V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Words read</small><strong>{{ number_format($totalWordsRead) }}</strong><span>{{ $continueBook ? 'Across your current book' : 'Start reading to track' }}</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-icon-coral">
                            <svg viewBox="0 0 22 22" fill="none"><rect x="7" y="3" width="8" height="12" rx="4" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 11.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5M11 18v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Speaking</small><strong>{{ $recentEntries > 0 ? $recentEntries : 0 }}</strong><span>{{ $recentEntries > 0 ? 'Words ready to practise' : 'Save words to practise speaking' }}</span></div>
                    </article>
                </section>

                <section class="dashboard-lower">
                    <article class="recommended-card">
                        <div class="card-heading">
                            <div><span>Recommended for you</span><h2>Your next short read</h2></div>
                            <a href="#">View library</a>
                        </div>
                        <div class="recommendation">
                            <div class="recommendation-cover"><span>English</span><strong>A Day in<br>London</strong></div>
                            <div>
                                <span class="level-pill level-easy">A1</span>
                                <h3>A Day in London</h3>
                                <p>A simple story about transport, food, and finding your way around a new city.</p>
                                <small>8 min read · 640 words</small>
                            </div>
                            <button type="button" aria-label="Open recommendation">→</button>
                        </div>
                    </article>

                    <article class="quick-actions-card">
                        <div class="card-heading"><div><span>Quick actions</span><h2>What next?</h2></div></div>
                        <div class="quick-actions">
                            <a href="{{ route('library.create') }}"><span>＋</span><div><strong>Upload a text</strong><small>TXT or EPUB</small></div></a>
                            <a href="{{ route('vocabulary.index') }}"><span>Aa</span><div><strong>Review vocabulary</strong><small>{{ $recentEntries }} {{ Str::plural('word', $recentEntries) }} saved</small></div></a>
                            <a href="{{ route('speaking.index') }}"><span>◉</span><div><strong>Speaking practice</strong><small>Start with a phrase</small></div></a>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>

    <div class="language-modal" data-language-modal hidden>
        <button class="modal-backdrop" type="button" data-close-languages aria-label="Close language settings"></button>
        <div class="language-modal-card">
            <button class="modal-close" type="button" data-close-languages aria-label="Close">×</button>
            <span class="section-kicker">Language settings</span>
            <h2>Make LingoRise yours.</h2>
            <p>Translations and explanations will use your native language.</p>
            <form class="language-modal-form" method="POST" action="{{ route('settings.languages') }}">
                @csrf
                @method('PUT')
                <label>
                    <span>Native language</span>
                    <select name="native_locale">
                        @foreach (['de' => 'German', 'ru' => 'Russian', 'sv' => 'Swedish', 'es' => 'Spanish', 'fr' => 'French', 'uk' => 'Ukrainian'] as $code => $language)
                            <option value="{{ $code }}" @selected(($preference?->native_locale ?? 'de') === $code)>{{ $language }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Learning language</span>
                    <select name="learning_locale">
                        @foreach (['en' => 'English', 'de' => 'German', 'es' => 'Spanish', 'fr' => 'French', 'sv' => 'Swedish'] as $code => $language)
                            <option value="{{ $code }}" @selected(($preference?->learning_locale ?? 'en') === $code)>{{ $language }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Save settings</button>
            </form>
        </div>
    </div>
</body>
</html>
