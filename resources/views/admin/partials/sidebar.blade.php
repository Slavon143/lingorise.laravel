@php
    $adminNav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard*', 'icon' => '⌘'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => '◎'],
        ['label' => 'Books', 'route' => 'admin.books.index', 'match' => 'admin.books.*', 'icon' => '▤'],
        ['label' => 'AI & TTS', 'route' => 'admin.ai.index', 'match' => 'admin.ai.*', 'icon' => '✦'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'icon' => '⚙'],
        ['label' => 'Audit logs', 'route' => 'admin.audit-logs.index', 'match' => 'admin.audit-logs.*', 'icon' => '◷'],
    ];
@endphp

<aside class="admin-sidebar" data-admin-sidebar>
    <a class="admin-brand" href="{{ route('admin.dashboard') }}">
        <span class="admin-brand-mark">LR</span>
        <span><strong>LingoRise</strong><small>Admin Console</small></span>
    </a>

    <nav class="admin-nav" aria-label="Admin navigation">
        @foreach($adminNav as $item)
            <a class="@if(request()->routeIs($item['match'])) is-active @endif" href="{{ route($item['route']) }}">
                <span>{{ $item['icon'] }}</span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="admin-sidebar-footer">
        <a href="{{ route('dashboard') }}">← Main site</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </div>
</aside>
