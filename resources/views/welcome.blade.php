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
            </nav>

            <div class="header-actions">
                <a class="login-link" href="#">Log in</a>
                <a class="button button-small" href="#start">Start for free</a>
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
                        <a class="button button-primary" href="#">
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
                <div class="feature-item" id="practice">
                    <span class="feature-number">03</span>
                    <div><strong>Listen</strong><p>Natural pronunciation</p></div>
                </div>
                <div class="feature-item" id="library">
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
                    <a class="button button-primary" href="#start">
                        Choose your language
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
