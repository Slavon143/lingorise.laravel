@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Start for free</span>
        <h2>Begin your next chapter.</h2>
        <p>Create an account and choose the language you want to bring to life.</p>
    </div>

    <a class="google-auth-button" href="{{ route('auth.google') }}">
        <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Sign up with Google
    </a>
    <div class="auth-divider"><span>or with your email</span></div>

    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <label class="form-field">
            <span>Your name</span>
            <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus placeholder="Alex Morgan">
            @error('name') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <label class="form-field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="you@example.com">
            @error('email') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <label class="form-field">
            <span>Password</span>
            <span class="password-field">
                <input type="password" name="password" autocomplete="new-password" required placeholder="At least 8 characters">
                <button class="password-toggle" type="button" aria-label="Show password">Show</button>
            </span>
            @error('password') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <label class="form-field">
            <span>Confirm password</span>
            <span class="password-field">
                <input type="password" name="password_confirmation" autocomplete="new-password" required placeholder="Repeat your password">
                <button class="password-toggle" type="button" aria-label="Show password">Show</button>
            </span>
        </label>

        <label class="terms-option">
            <input type="checkbox" required>
            <span>I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</span>
        </label>

        <button class="auth-submit" type="submit">Create my account <span>→</span></button>
    </form>

    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
