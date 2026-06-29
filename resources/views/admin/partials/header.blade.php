<header class="admin-header">
    <button class="admin-menu-button" type="button" data-admin-menu aria-label="Open admin navigation">
        <span></span><span></span><span></span>
    </button>

    <div>
        <span class="admin-kicker">@yield('eyebrow', 'Control center')</span>
        <h1>@yield('title', 'Admin')</h1>
    </div>

    <div class="admin-profile">
        <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        <div>
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->email }}</small>
        </div>
    </div>
</header>
