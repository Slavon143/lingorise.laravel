<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · LingoRise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="app-sidebar">
            <button class="sidebar-collapse-button" type="button" data-sidebar-collapse aria-label="Collapse sidebar" title="Collapse sidebar">
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <rect x="2.5" y="3" width="15" height="14" rx="2"></rect>
                    <path d="M7 3v14M12.5 7.25 10 10l2.5 2.75"></path>
                </svg>
                <span>Collapse</span>
            </button>
            <a class="brand app-brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none"><path d="M8 24V9.5C8 7.57 9.57 6 11.5 6H24v15.5c0 1.38-1.12 2.5-2.5 2.5H8Z" stroke="currentColor" stroke-width="2.2"/><path d="M12 11h7M12 15h5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M8 24c0-1.66 1.34-3 3-3h13" stroke="currentColor" stroke-width="2.2"/></svg>
                </span>
                <span>Lingo<span>Rise</span></span>
            </a>
            <nav class="app-nav">
                <a class="@if(request()->routeIs('dashboard')) is-active @endif" href="{{ route('dashboard') }}"><span>⌂</span><span>Home</span></a>
                <a class="@if(request()->routeIs('library.*')) is-active @endif" href="{{ route('library.index') }}"><span>▤</span><span>My library</span></a>
                <a class="@if(request()->routeIs('vocabulary.*')) is-active @endif" href="{{ route('vocabulary.index') }}"><span>Aa</span><span>Vocabulary</span><small>{{ auth()->user()->dictionaryEntries()->count() }}</small></a>
                <a href="#"><span>◉</span><span>Speaking</span></a>
                <a href="#"><span>↗</span><span>Progress</span></a>
            </nav>
            <div class="sidebar-bottom">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sidebar-logout" type="submit">← Log out</button>
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
</body>
</html>
