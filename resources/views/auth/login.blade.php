@extends('layouts.auth')

@section('title', 'Log in')

@section('content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Welcome back</span>
        <h2>Continue your story.</h2>
        <p>Log in to return to your library and learning progress.</p>
    </div>

    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf

        <label class="form-field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="you@example.com">
            @error('email') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <label class="form-field">
            <span>Password</span>
            <span class="password-field">
                <input type="password" name="password" autocomplete="current-password" required placeholder="Your password">
                <button class="password-toggle" type="button" aria-label="Show password">Show</button>
            </span>
            @error('password') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <div class="form-options">
            <label class="remember-option">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>
            <a href="#">Forgot password?</a>
        </div>

        <button class="auth-submit" type="submit">Log in <span>→</span></button>
    </form>

    <p class="auth-switch">New to LingoRise? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
