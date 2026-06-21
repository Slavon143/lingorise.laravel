<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · LingoRise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page" data-speech-url="{{ route('speech.create') }}">
    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="brand app-brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none"><path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/><path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/></svg>
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
                <a class="@if(request()->routeIs('dashboard')) is-active @endif" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M4 10.5 11 4l7 6.5V18a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    <span>Home</span>
                </a>
                <a class="@if(request()->routeIs('library.*')) is-active @endif" href="{{ route('library.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 4h10a2 2 0 0 1 2 2v12H7a2 2 0 0 1-2-2V4Zm0 10h12M8 7h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>My library</span>
                </a>
                <a class="@if(request()->routeIs('library.public')) is-active @endif" href="{{ route('library.public') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M3 11h16M7 4v7M11 4v7M15 4v7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="3" y="11" width="16" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
                    <span>Public library</span>
                </a>
                <a class="@if(request()->routeIs('vocabulary.*')) is-active @endif" href="{{ route('vocabulary.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Vocabulary</span>
                    <small>{{ auth()->user()->dictionaryEntries()->count() }}</small>
                </a>
                <a class="@if(request()->routeIs('speaking.*')) is-active @endif" href="{{ route('speaking.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><rect x="7" y="3" width="8" height="12" rx="4" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 11.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5M11 18v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Speaking</span>
                </a>
                <a class="@if(request()->routeIs('progress.*')) is-active @endif" href="{{ route('progress.index') }}">
                    <svg viewBox="0 0 22 22" fill="none"><path d="M4 18V9m5 9V4m5 14v-6m5 6V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span>Progress</span>
                </a>
            </nav>
            <div class="sidebar-bottom">
                @php
                    $sidebarPref = auth()->user()->languagePreference;
                    $sidebarLanguages = ['en' => 'English', 'de' => 'German', 'es' => 'Spanish', 'fr' => 'French', 'sv' => 'Swedish'];
                    $sidebarLearning = $sidebarLanguages[$sidebarPref?->learning_locale] ?? 'English';
                @endphp
                @if($sidebarPref)
                    <button class="language-summary" type="button" data-open-languages>
                        <span class="language-flag">{{ strtoupper($sidebarPref->learning_locale) }}</span>
                        <span><small>Learning</small><strong>{{ $sidebarLearning }}</strong></span>
                        <span>⌄</span>
                    </button>
                @endif
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
                <button class="mobile-menu-button" type="button" aria-label="Open navigation"><span></span><span></span><span></span></button>
                <div class="topbar-search"><span>⌕</span><input type="search" placeholder="Search your library"></div>
                <div class="topbar-actions">
                    <a class="topbar-add-book" href="{{ route('library.create') }}">＋ Add a book</a>
                    <button class="user-button" type="button">
                        <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <div><strong>{{ auth()->user()->name }}</strong><small>Free plan</small></div>
                    </button>
                </div>
            </header>
            <div class="dashboard-content">
                @if(session('status')) <div class="dashboard-alert">{{ session('status') }}</div> @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('modals')
    @stack('scripts')
</body>
</html>
