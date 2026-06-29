@extends('admin.layouts.app')

@section('title', 'Users')
@section('eyebrow', 'Identity & access')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Directory</span>
                <h2>User accounts</h2>
            </div>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.users.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Name or email">
            </label>
            <label>
                <span>Admin status</span>
                <select name="admin">
                    <option value="">All users</option>
                    <option value="yes" @selected(request('admin') === 'yes')>Admins only</option>
                    <option value="no" @selected(request('admin') === 'no')>Non-admins</option>
                </select>
            </label>
            <label>
                <span>Sort by</span>
                <select name="sort">
                    @foreach(['created_at' => 'Created at', 'updated_at' => 'Updated at', 'name' => 'Name', 'email' => 'Email', 'id' => 'ID'] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Direction</span>
                <select name="direction">
                    <option value="desc" @selected($direction === 'desc')>Newest first</option>
                    <option value="asc" @selected($direction === 'asc')>Oldest first</option>
                </select>
            </label>
            <button type="submit">Apply filters</button>
            <a href="{{ route('admin.users.index') }}">Reset</a>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>Created at</th>
                        <th>Last updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>#{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="admin-badge {{ $user->is_admin ? 'is-admin' : '' }}">
                                    {{ $user->is_admin ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td>{{ $user->created_at?->format('M d, Y') }}</td>
                            <td>{{ $user->updated_at?->format('M d, Y') }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.users.show', $user) }}">View</a>
                                    <a href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-empty-cell">No users match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $users->links() }}
        </div>
    </section>
@endsection
