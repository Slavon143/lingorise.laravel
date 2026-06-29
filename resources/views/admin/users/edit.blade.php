@extends('admin.layouts.app')

@section('title', 'Edit user')
@section('eyebrow', 'Safe profile update')

@section('content')
    <section class="admin-panel admin-form-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Allowed fields</span>
                <h2>{{ $managedUser->name }}</h2>
            </div>
            <a class="admin-link-button" href="{{ route('admin.users.show', $managedUser) }}">View user</a>
        </div>

        <form class="admin-form" method="POST" action="{{ route('admin.users.update', $managedUser) }}">
            @csrf
            @method('PATCH')

            <label>
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name', $managedUser->name) }}" required maxlength="100">
                @error('name') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email', $managedUser->email) }}" required maxlength="255">
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            <div class="admin-form-note">
                Admin rights are changed only with the separate action buttons on the user profile page.
            </div>

            <div class="admin-form-actions">
                <button class="admin-primary-button" type="submit">Save changes</button>
                <a href="{{ route('admin.users.show', $managedUser) }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
