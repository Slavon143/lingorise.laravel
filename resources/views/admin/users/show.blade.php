@extends('admin.layouts.app')

@section('title', 'User #'.$managedUser->id)
@section('eyebrow', 'Account profile')

@section('content')
    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <span class="admin-kicker">Identity</span>
                    <h2>{{ $managedUser->name }}</h2>
                </div>
                <a class="admin-link-button" href="{{ route('admin.users.edit', $managedUser) }}">Edit user</a>
            </div>

            <dl class="admin-details">
                <div><dt>ID</dt><dd>#{{ $managedUser->id }}</dd></div>
                <div><dt>Name</dt><dd>{{ $managedUser->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $managedUser->email }}</dd></div>
                <div><dt>Admin status</dt><dd><span class="admin-badge {{ $managedUser->is_admin ? 'is-admin' : '' }}">{{ $managedUser->is_admin ? 'Admin' : 'User' }}</span></dd></div>
                <div><dt>Plan</dt><dd>{{ ucfirst($managedUser->plan ?? 'free') }}</dd></div>
                <div><dt>Books</dt><dd>{{ number_format($managedUser->books_count) }}</dd></div>
                <div><dt>Registered at</dt><dd>{{ $managedUser->created_at?->format('M d, Y H:i') }}</dd></div>
                <div><dt>Updated at</dt><dd>{{ $managedUser->updated_at?->format('M d, Y H:i') }}</dd></div>
            </dl>
        </article>

        <aside class="admin-panel admin-action-panel">
            <span class="admin-kicker">Access control</span>
            <h2>Admin rights</h2>
            <p>Promotion and demotion are separate protected actions and are written to the audit log.</p>

            @if($managedUser->is_admin)
                <form method="POST" action="{{ route('admin.users.demote', $managedUser) }}" onsubmit="return confirm('Remove administrator rights from this user?')">
                    @csrf
                    <button class="admin-danger-button" type="submit">Remove admin rights</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.promote', $managedUser) }}" onsubmit="return confirm('Grant administrator rights to this user?')">
                    @csrf
                    <button class="admin-primary-button" type="submit">Make administrator</button>
                </form>
            @endif

            <a class="admin-muted-link" href="{{ route('admin.users.index') }}">← Back to users</a>
        </aside>
    </section>
@endsection
