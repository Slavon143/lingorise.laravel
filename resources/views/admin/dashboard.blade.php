@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', 'Live overview')

@section('content')
    <div class="admin-stats-grid">
        <article class="admin-stat-card">
            <span>Total users</span>
            <strong>{{ number_format($totalUsers) }}</strong>
            <small>All registered accounts</small>
        </article>
        <article class="admin-stat-card">
            <span>New users</span>
            <strong>{{ number_format($newUsers) }}</strong>
            <small>Last 7 days</small>
        </article>
        <article class="admin-stat-card">
            <span>Administrators</span>
            <strong>{{ number_format($adminUsers) }}</strong>
            <small>Accounts with admin access</small>
        </article>
        <article class="admin-stat-card">
            <span>New users</span>
            <strong>{{ number_format($newUsers30d) }}</strong>
            <small>Last 30 days</small>
        </article>
        <article class="admin-stat-card">
            <span>Books</span>
            <strong>{{ number_format($booksCount) }}</strong>
            <small>Total in database</small>
        </article>
        <article class="admin-stat-card">
            <span>Public books</span>
            <strong>{{ number_format($publicBooksCount) }}</strong>
            <small>Visible in public library</small>
        </article>
        <article class="admin-stat-card admin-stat-card-wide">
            <span>Last registration</span>
            <strong>{{ $lastRegistration ? $lastRegistration->format('M d, Y H:i') : 'No users yet' }}</strong>
            <small>Most recent account creation</small>
        </article>
        <article class="admin-stat-card admin-stat-card-wide is-healthy">
            <span>Application state</span>
            <strong>Operational</strong>
            <small>{{ app()->environment() }} environment</small>
        </article>
    </div>

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Latest activity</span>
                <h2>Recent users</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.users.index') }}">View all users</a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Registered at</th>
                        <th>Admin status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestUsers as $user)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->created_at?->format('M d, Y H:i') }}</td>
                            <td>
                                <span class="admin-badge {{ $user->is_admin ? 'is-admin' : '' }}">
                                    {{ $user->is_admin ? 'Admin' : 'User' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="admin-empty-cell">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
