@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Start for free</span>
        <h2>Begin your next chapter.</h2>
        <p>Create an account and choose the language you want to bring to life.</p>
    </div>

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
