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

            <nav class="app-nav" aria-label="Dashboard navigation">
                <a class="is-active" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M4 10.5 11 4l7 6.5V18a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    <span>Home</span>
                </a>
                <a href="#">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 4h10a2 2 0 0 1 2 2v12H7a2 2 0 0 1-2-2V4Zm0 10h12M8 7h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>My library</span>
                </a>
                <a href="#">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Vocabulary</span>
                    <small>0</small>
                </a>
                <a href="#">
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
                    <section class="onboarding-card" id="language-setup">
                        <div class="onboarding-copy">
                            <span class="section-kicker">First things first</span>
                            <h2>Which language<br>do you want to bring to life?</h2>
                            <p>We’ll use your native language for translations and explanations. The app interface stays in English.</p>
                        </div>
                        <form class="language-form" method="POST" action="{{ route('settings.languages') }}">
                            @csrf
                            @method('PUT')
                            <label>
                                <span>I speak</span>
                                <select name="native_locale" required>
                                    <option value="de" selected>German</option>
                                    <option value="ru">Russian</option>
                                    <option value="sv">Swedish</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="uk">Ukrainian</option>
                                </select>
                            </label>
                            <span class="language-arrow">→</span>
                            <label>
                                <span>I want to learn</span>
                                <select name="learning_locale" required>
                                    <option value="en" selected>English</option>
                                    <option value="de">German</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="sv">Swedish</option>
                                </select>
                            </label>
                            <button type="submit">Save and continue <span>→</span></button>
                        </form>
                        @error('native_locale') <small class="onboarding-error">{{ $message }}</small> @enderror
                        @error('learning_locale') <small class="onboarding-error">{{ $message }}</small> @enderror
                    </section>
                @endunless

                <section class="dashboard-grid">
                    <article class="continue-card">
                        <div class="card-heading">
                            <div><span>Continue reading</span><h2>The Secret Garden</h2></div>
                            <span class="level-pill level-easy">A2</span>
                        </div>
                        <div class="continue-body">
                            <div class="continue-cover">
                                <div class="cover-art flower-art"><i></i><i></i><i></i><i></i><i></i></div>
                                <small>Frances Hodgson Burnett</small>
                                <strong>The Secret<br>Garden</strong>
                            </div>
                            <div class="continue-details">
                                <p>“When Mary Lennox was sent to Misselthwaite Manor…”</p>
                                <div class="reading-progress">
                                    <div><span>Reading progress</span><strong>12%</strong></div>
                                    <div class="dashboard-progress"><i></i></div>
                                    <small>Page 8 of 64 · 18 min left</small>
                                </div>
                                <a href="#">Continue reading <span>→</span></a>
                            </div>
                        </div>
                    </article>

                    <article class="daily-goal-card">
                        <div class="card-heading"><div><span>Daily goal</span><h2>Keep it light.</h2></div><button type="button">•••</button></div>
                        <div class="goal-ring">
                            <div><strong>4</strong><span>/ 10 min</span><small>today</small></div>
                        </div>
                        <p>Six more minutes will keep your streak alive.</p>
                    </article>

                    <article class="stat-card">
                        <span class="stat-icon stat-icon-blue">
                            <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Words saved</small><strong>0</strong><span>Your vocabulary starts here</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-icon-green">
                            <svg viewBox="0 0 22 22" fill="none"><path d="M4 18V9m5 9V4m5 14v-6m5 6V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Words read</small><strong>326</strong><span>Across your current book</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-icon-coral">
                            <svg viewBox="0 0 22 22" fill="none"><rect x="7" y="3" width="8" height="12" rx="4" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 11.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5M11 18v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <div><small>Speaking</small><strong>0</strong><span>Practice your first phrase</span></div>
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
                            <a href="#"><span>＋</span><div><strong>Upload a text</strong><small>TXT or EPUB</small></div></a>
                            <a href="#"><span>Aa</span><div><strong>Review vocabulary</strong><small>0 words waiting</small></div></a>
                            <a href="#"><span>◉</span><div><strong>Speaking practice</strong><small>Start with a phrase</small></div></a>
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
