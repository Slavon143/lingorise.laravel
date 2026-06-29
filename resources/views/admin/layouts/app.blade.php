<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · LingoRise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar')
        <button class="admin-backdrop" type="button" data-admin-backdrop aria-label="Close admin navigation" hidden></button>

        <main class="admin-main">
            @include('admin.partials.header')

            <section class="admin-content">
                @if(session('status'))
                    <div class="admin-flash">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="admin-errors">
                        <strong>Please check the form.</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
