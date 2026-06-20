<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Join LingoRise and learn languages through stories.')">
    <title>@yield('title') · LingoRise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story-panel">
            <a class="brand auth-brand" href="{{ route('home') }}" aria-label="LingoRise home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/>
                        <path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/>
                    </svg>
                </span>
                <span>Lingo<span>Rise</span></span>
            </a>

            <div class="auth-story-copy">
                <span class="section-kicker">Learn through meaning</span>
                <h1>Every story gives<br>you something<br><em>new to say.</em></h1>
                <p>Read what interests you, understand every word, and build the confidence to speak.</p>
            </div>

            <div class="auth-quote">
                <p>“The first language app that makes me forget I’m studying.”</p>
                <span>— Anna, learning English</span>
            </div>

            <span class="auth-word auth-word-one">wonderful</span>
            <span class="auth-word auth-word-two">großartig</span>
        </section>

        <section class="auth-form-panel">
            <a class="auth-back" href="{{ route('home') }}">← Back to home</a>
            <div class="auth-form-wrap">
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
