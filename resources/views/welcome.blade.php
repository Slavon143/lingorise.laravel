<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="LingoRise — learn languages through books, real phrases, and smart practice.">
    <title>LingoRise — languages through stories</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="/" aria-label="LingoRise">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/>
                        <path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/>
                    </svg>
                </span>
                <span>Lingo<span>Rise</span></span>
            </a>

            <nav class="desktop-nav" aria-label="Main navigation">
                <a href="#how">How it works</a>
                <a href="#practice">Practice</a>
                <a href="#library">Library</a>
                <a href="#pricing">Pricing</a>
            </nav>

            <div class="header-actions">
                <a class="login-link" href="{{ route('login') }}">Log in</a>
                <a class="button button-small" href="{{ route('register') }}">Start for free</a>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="hero-copy">
                    <div class="eyebrow"><span></span> Read. Listen. Speak.</div>
                    <h1>Language comes<br><em>alive</em> when<br>you care.</h1>
                    <p class="hero-lead">
                        Read stories you love, translate words with one tap,
                        and turn every text into a lesson made for you.
                    </p>

                    <div class="hero-actions" id="start">
                        <a class="button button-primary" href="{{ route('register') }}">
                            Try it for free
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a class="watch-link" href="#how">
                            <span class="play"><svg viewBox="0 0 18 18"><path d="m7 5 5 4-5 4V5Z" fill="currentColor"/></svg></span>
                            See how it works
                        </a>
                    </div>

                    <div class="trust-row">
                        <div class="avatars" aria-hidden="true">
                            <span>AN</span><span>MK</span><span>JL</span><span>+2k</span>
                        </div>
                        <div>
                            <div class="stars">★★★★★</div>
                            <p>Learning every day</p>
                        </div>
                    </div>
                </div>

                <div class="reader-stage" aria-label="Interactive reader preview">
                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div>
                    <div class="floating-word word-hola">Hola!</div>
                    <div class="floating-word word-bonjour">Bonjour</div>
                    <div class="floating-word word-hej">Hej!</div>

                    <article class="reader-card">
                        <div class="reader-topbar">
                            <div class="window-dots"><span></span><span></span><span></span></div>
                            <div class="reader-title">The Secret Garden</div>
                            <button aria-label="Reader settings">
                                <svg viewBox="0 0 20 20" fill="none"><path d="M4 6h12M7 10h6M9 14h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <div class="reader-content">
                            <div class="chapter-label">Chapter 1</div>
                            <h2>There is no one left</h2>
                            <p>
                                When Mary Lennox was sent to Misselthwaite Manor to live
                                with her uncle everybody said she was the most
                                <span class="reader-word-anchor">
                                    <button class="selected-word" type="button">disagreeable</button>
                                    <span class="translation-popover">
                                        <span class="translation-head">
                                            <strong>disagreeable</strong>
                                            <button class="sound-button" aria-label="Listen to the word">
                                                <svg viewBox="0 0 20 20" fill="none"><path d="M4 8v4h3l4 3V5L7 8H4Zm10 0c.8 1.1.8 2.9 0 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </span>
                                        <span class="phonetic">/ˌdɪsəˈɡriːəbl/</span>
                                        <span class="translation-language">German translation</span>
                                        <span class="translation">unangenehm, unsympathisch</span>
                                        <span class="native-explanation">Eine Person, mit der man nur schwer auskommt oder sich einigen kann.</span>
                                        <button class="save-word" type="button">
                                            <span>＋</span> Add to vocabulary
                                        </button>
                                    </span>
                                </span>
                                child ever seen.
                            </p>
                            <p>
                                It was true, too. She had a little thin face and a little
                                thin body, thin light hair and a sour expression.
                            </p>

                        </div>

                        <div class="reader-footer">
                            <span>12%</span>
                            <div class="progress"><i></i></div>
                            <span>8 / 64</span>
                        </div>
                    </article>

                    <div class="streak-card">
                        <span class="flame">◆</span>
                        <div><strong>7 days</strong><small>Learning streak</small></div>
                    </div>
                </div>
            </section>

            <section class="feature-strip" id="how">
                <div class="feature-intro">
                    <span>One story</span>
                    <strong>four skills</strong>
                </div>
                <div class="feature-item">
                    <span class="feature-number">01</span>
                    <div><strong>Read</strong><p>Stories at your level</p></div>
                </div>
                <div class="feature-item">
                    <span class="feature-number">02</span>
                    <div><strong>Understand</strong><p>One-tap translation</p></div>
                </div>
                <div class="feature-item">
                    <span class="feature-number">03</span>
                    <div><strong>Listen</strong><p>Natural pronunciation</p></div>
                </div>
                <div class="feature-item">
                    <span class="feature-number">04</span>
                    <div><strong>Speak</strong><p>Practice with feedback</p></div>
                </div>
            </section>

            <section class="how-section" aria-labelledby="how-title">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">How it works</span>
                        <h2 id="how-title">From a story you love<br>to words you can use.</h2>
                    </div>
                    <p>
                        No isolated word lists or artificial exercises. LingoRise builds
                        each lesson around meaningful content and your personal progress.
                    </p>
                </div>

                <div class="steps-grid">
                    <article class="step-card step-card-library">
                        <div class="step-meta">
                            <span>01</span>
                            <span>Choose</span>
                        </div>
                        <h3>Start with something<br>you want to read.</h3>
                        <p>Pick a story from the library or upload your own TXT or EPUB file.</p>

                        <div class="step-visual library-visual" aria-hidden="true">
                            <div class="mini-book book-blue">
                                <span>Short stories</span>
                                <strong>EN</strong>
                            </div>
                            <div class="mini-book book-coral">
                                <span>Travel notes</span>
                                <strong>ES</strong>
                            </div>
                            <div class="mini-book book-lime">
                                <span>Your EPUB</span>
                                <strong>＋</strong>
                            </div>
                        </div>
                    </article>

                    <article class="step-card step-card-reader">
                        <div class="step-meta">
                            <span>02</span>
                            <span>Explore</span>
                        </div>
                        <h3>Read naturally.<br>Understand instantly.</h3>
                        <p>Tap any word or phrase for translation, pronunciation, and a clear explanation.</p>

                        <div class="step-visual phrase-visual" aria-hidden="true">
                            <p>The garden was full of <mark class="translated-word">wonderful</mark> secrets.</p>
                            <div class="phrase-definition">
                                <div class="translation-head">
                                    <strong>wonderful</strong>
                                    <button class="sound-button" tabindex="-1">
                                        <svg viewBox="0 0 20 20" fill="none"><path d="M4 8v4h3l4 3V5L7 8H4Zm10 0c.8 1.1.8 2.9 0 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                                <div class="phonetic">/ˈwʌndəfəl/</div>
                                <div class="translation-language">German translation</div>
                                <div class="translation">wunderbar, großartig</div>
                                <div class="native-explanation">Etwas, das besonders schön ist oder große Freude auslöst.</div>
                                <button class="save-word" tabindex="-1">
                                    <span>＋</span> Add to vocabulary
                                </button>
                            </div>
                        </div>
                    </article>

                    <article class="step-card step-card-practice">
                        <div class="step-meta">
                            <span>03</span>
                            <span>Remember</span>
                        </div>
                        <h3>Turn new words<br>into active language.</h3>
                        <p>Review your vocabulary, hear natural speech, and practise speaking with feedback.</p>

                        <div class="step-visual practice-visual" aria-hidden="true">
                            <div class="practice-ring">
                                <span>84%</span>
                                <small>great job</small>
                            </div>
                            <div class="sound-wave">
                                <i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="how-cta">
                    <div>
                        <span class="cta-dot"></span>
                        <p><strong>Your next language</strong> can begin with one good story.</p>
                    </div>
                    <a class="button button-primary" href="{{ route('register') }}">
                        Choose your language
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </section>

            <section class="library-section" id="library" aria-labelledby="library-title">
                <div class="library-heading">
                    <div>
                        <span class="section-kicker">Your library</span>
                        <h2 id="library-title">Find a story that<br>pulls you in.</h2>
                    </div>
                    <div class="library-copy">
                        <p>
                            Explore short stories, classics, and practical reads selected
                            for language learners—or bring a book of your own.
                        </p>
                        <div class="library-filters" aria-label="Library filters">
                            <button class="filter-chip is-active" type="button" data-filter="all">All stories</button>
                            <button class="filter-chip" type="button" data-filter="beginner">Beginner</button>
                            <button class="filter-chip" type="button" data-filter="intermediate">Intermediate</button>
                        </div>
                    </div>
                </div>

                <div class="book-grid">
                    <article class="book-card" data-level="beginner">
                        <div class="book-cover cover-garden">
                            <span class="cover-language">English</span>
                            <div class="cover-art flower-art">
                                <i></i><i></i><i></i><i></i><i></i>
                            </div>
                            <div class="cover-title">
                                <small>Frances Hodgson Burnett</small>
                                <strong>The Secret<br>Garden</strong>
                            </div>
                        </div>
                        <div class="book-info">
                            <div>
                                <span class="level-pill level-easy">A2</span>
                                <span>18 min read</span>
                            </div>
                            <button aria-label="Open The Secret Garden">
                                <svg viewBox="0 0 20 20" fill="none"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </article>

                    <article class="book-card" data-level="intermediate">
                        <div class="book-cover cover-sea">
                            <span class="cover-language">English</span>
                            <div class="cover-art moon-art"><i></i><span></span></div>
                            <div class="cover-title">
                                <small>Oscar Wilde</small>
                                <strong>The Happy<br>Prince</strong>
                            </div>
                        </div>
                        <div class="book-info">
                            <div>
                                <span class="level-pill level-mid">B1</span>
                                <span>24 min read</span>
                            </div>
                            <button aria-label="Open The Happy Prince">
                                <svg viewBox="0 0 20 20" fill="none"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </article>

                    <article class="book-card" data-level="beginner">
                        <div class="book-cover cover-city">
                            <span class="cover-language">English</span>
                            <div class="cover-art city-art"><i></i><i></i><i></i><i></i></div>
                            <div class="cover-title">
                                <small>LingoRise original</small>
                                <strong>A Day in<br>London</strong>
                            </div>
                        </div>
                        <div class="book-info">
                            <div>
                                <span class="level-pill level-easy">A1</span>
                                <span>8 min read</span>
                            </div>
                            <button aria-label="Open A Day in London">
                                <svg viewBox="0 0 20 20" fill="none"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </article>

                    <article class="book-card upload-card" data-level="all">
                        <div class="upload-icon">
                            <svg viewBox="0 0 28 28" fill="none"><path d="M14 19V7m0 0-5 5m5-5 5 5M7 18v3h14v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <span class="upload-label">Your own material</span>
                        <h3>Upload a book<br>you already love.</h3>
                        <p>TXT and EPUB files up to 10 MB.</p>
                        <button class="upload-button" type="button">Choose a file</button>
                    </article>
                </div>

                <div class="library-footer">
                    <p><span>120+</span> learner-friendly stories and growing</p>
                    <a href="#">Browse the full library <span>→</span></a>
                </div>
            </section>

            <section class="practice-section" id="practice" aria-labelledby="practice-title">
                <div class="practice-copy">
                    <span class="section-kicker">Speaking practice</span>
                    <h2 id="practice-title">Build the confidence<br>to say it out loud.</h2>
                    <p>
                        Practise useful phrases from your reading. Listen to natural speech,
                        record your voice, and get clear feedback on pronunciation.
                    </p>

                    <div class="practice-points">
                        <div>
                            <span class="point-icon">
                                <svg viewBox="0 0 22 22" fill="none"><path d="M4 9v4h4l5 4V5L8 9H4Zm12-.5c1.1 1.4 1.1 3.6 0 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div><strong>Hear natural rhythm</strong><small>Multiple voices and playback speeds</small></div>
                        </div>
                        <div>
                            <span class="point-icon">
                                <svg viewBox="0 0 22 22" fill="none"><rect x="7" y="3" width="8" height="12" rx="4" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 11.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5M11 18v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </span>
                            <div><strong>Speak without pressure</strong><small>Private practice at your own pace</small></div>
                        </div>
                        <div>
                            <span class="point-icon">
                                <svg viewBox="0 0 22 22" fill="none"><path d="m4 12 4 4L18 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div><strong>Know what to improve</strong><small>Instant, focused pronunciation feedback</small></div>
                        </div>
                    </div>

                    <a class="button button-primary" href="{{ route('register') }}">
                        Start speaking
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>

                <div class="speaking-demo">
                    <div class="demo-glow"></div>
                    <div class="conversation-card">
                        <div class="conversation-topbar">
                            <div>
                                <span>Speaking session</span>
                                <strong>At the coffee shop</strong>
                            </div>
                            <span class="difficulty-badge">A2</span>
                        </div>

                        <div class="conversation-body">
                            <div class="speaker-row">
                                <span class="speaker-avatar">EM</span>
                                <div class="speech-bubble">
                                    <small>Listen and repeat</small>
                                    <p>Could I have a cup of coffee, please?</p>
                                    <button class="play-phrase" type="button" aria-label="Play phrase">
                                        <svg viewBox="0 0 18 18"><path d="m7 5 5 4-5 4V5Z" fill="currentColor"/></svg>
                                        <span class="phrase-wave"><i></i><i></i><i></i><i></i><i></i><i></i></span>
                                    </button>
                                </div>
                            </div>

                            <div class="record-panel">
                                <div class="record-state">
                                    <span class="record-dot"></span>
                                    <div><strong>Your turn</strong><small>Tap the microphone and repeat</small></div>
                                </div>
                                <button class="record-button" type="button" aria-label="Start voice recording">
                                    <svg viewBox="0 0 26 26" fill="none"><rect x="9" y="4" width="8" height="13" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M6 13c0 3.87 3.13 7 7 7s7-3.13 7-7M13 20v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </button>
                                <div class="record-wave" aria-hidden="true">
                                    <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                                </div>
                            </div>

                            <div class="feedback-panel">
                                <div class="score-circle"><strong>88</strong><small>score</small></div>
                                <div class="feedback-copy">
                                    <span>Great pronunciation</span>
                                    <p>Your rhythm sounds natural. Keep the final <mark>please</mark> a little softer.</p>
                                </div>
                            </div>
                        </div>

                        <div class="conversation-progress">
                            <span>Phrase 3 of 8</span>
                            <div><i></i></div>
                            <button type="button">Next <span>→</span></button>
                        </div>
                    </div>

                    <div class="floating-stat stat-words">
                        <span>+12</span>
                        <small>words practised</small>
                    </div>
                    <div class="floating-stat stat-confidence">
                        <svg viewBox="0 0 20 20" fill="none"><path d="m4 11 4 4 8-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <small>Confidence up</small>
                    </div>
                </div>
            </section>

            <section class="pricing-section" id="pricing" aria-labelledby="pricing-title">
                <div class="pricing-heading">
                    <span class="section-kicker">Simple pricing</span>
                    <h2 id="pricing-title">Start with a story.<br>Grow at your pace.</h2>
                    <p>Try the core experience for free. Upgrade when you want deeper practice and unlimited reading.</p>
                </div>

                <div class="pricing-grid">
                    <article class="price-card">
                        <div class="price-card-head">
                            <div>
                                <span class="plan-label">Free</span>
                                <h3>Explore LingoRise</h3>
                            </div>
                            <div class="price"><strong>€0</strong><span>forever</span></div>
                        </div>
                        <p class="plan-description">A relaxed way to discover reading-based language learning.</p>
                        <ul class="plan-features">
                            <li><span>✓</span> Access to selected stories</li>
                            <li><span>✓</span> Instant word translation</li>
                            <li><span>✓</span> Personal vocabulary list</li>
                            <li><span>✓</span> Basic reading progress</li>
                        </ul>
                        <a class="plan-button plan-button-secondary" href="{{ route('register') }}">Start for free</a>
                    </article>

                    <article class="price-card price-card-featured">
                        <div class="popular-label">Most popular</div>
                        <div class="price-card-head">
                            <div>
                                <span class="plan-label">LingoRise Pro</span>
                                <h3>Make it a daily habit</h3>
                            </div>
                            <div class="price"><strong>€9</strong><span>per month</span></div>
                        </div>
                        <p class="plan-description">Everything you need to read, listen, remember, and speak with confidence.</p>
                        <ul class="plan-features">
                            <li><span>✓</span> Full learner library</li>
                            <li><span>✓</span> Upload unlimited TXT and EPUB books</li>
                            <li><span>✓</span> AI explanations in your native language</li>
                            <li><span>✓</span> Natural text-to-speech</li>
                            <li><span>✓</span> Speaking feedback and pronunciation scores</li>
                            <li><span>✓</span> Advanced progress insights</li>
                        </ul>
                        <a class="plan-button plan-button-primary" href="{{ route('register') }}">Try Pro for free <span>→</span></a>
                        <small class="trial-note">7-day free trial · Cancel anytime</small>
                    </article>
                </div>
            </section>

            <section class="final-cta" aria-labelledby="final-cta-title">
                <div class="cta-orbit cta-orbit-one"></div>
                <div class="cta-orbit cta-orbit-two"></div>
                <span class="cta-word cta-word-hallo">Hallo!</span>
                <span class="cta-word cta-word-hello">Hello!</span>
                <span class="cta-word cta-word-hola">¡Hola!</span>

                <div class="final-cta-content">
                    <span class="section-kicker">Your next chapter</span>
                    <h2 id="final-cta-title">A new language is<br>one story away.</h2>
                    <p>Choose what interests you. We’ll turn it into a learning journey made for you.</p>
                    <a class="button final-cta-button" href="{{ route('register') }}">
                        Start learning for free
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <small>No credit card required</small>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-main">
                <div class="footer-brand">
                    <a class="brand brand-light" href="/" aria-label="LingoRise">
                        <span class="brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 32 32" fill="none">
                                <path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/>
                                <path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/>
                            </svg>
                        </span>
                        <span>Lingo<span>Rise</span></span>
                    </a>
                    <p>Learn languages through stories worth reading.</p>
                </div>

                <div class="footer-links">
                    <div>
                        <strong>Product</strong>
                        <a href="#how">How it works</a>
                        <a href="#library">Library</a>
                        <a href="#practice">Speaking practice</a>
                        <a href="#pricing">Pricing</a>
                    </div>
                    <div>
                        <strong>Company</strong>
                        <a href="#">About</a>
                        <a href="#">Contact</a>
                        <a href="#">Help center</a>
                    </div>
                    <div>
                        <strong>Legal</strong>
                        <a href="#">Privacy</a>
                        <a href="#">Terms</a>
                        <a href="#">Cookies</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ date('Y') }} LingoRise. All rights reserved.</span>
                <div>
                    <button type="button">English <span>⌄</span></button>
                    <span>Made for curious minds.</span>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
