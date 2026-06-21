@extends('layouts.app')

@section('title', 'Pricing')

@section('content')
    <section class="pricing-heading">
        <span class="dashboard-date">Simple, transparent pricing</span>
        <h1>Choose the plan<br>that fits your pace.</h1>
        <p>Start free, upgrade when you&rsquo;re ready for more.</p>
    </section>

    <section class="pricing-grid">
        <article class="pricing-card @if(!auth()->user()->isPro()) pricing-card-featured @endif">
            <div class="pricing-card-header">
                <span class="pricing-name">Free</span>
                <div class="pricing-price"><strong>$0</strong><small>/month</small></div>
                <p>Perfect for getting started with reading and vocabulary.</p>
            </div>
            <ul class="pricing-features">
                <li><i>✓</i> Upload your own books (TXT, EPUB)</li>
                <li><i>✓</i> Built-in reader with translator</li>
                <li><i>✓</i> Vocabulary with context</li>
                <li><i>✓</i> Speaking practice (browser-based)</li>
                <li><i>✗</i> AI pronunciation (OpenAI TTS)</li>
                <li><i>✗</i> Unlimited vocabulary entries</li>
                <li><i>✗</i> Priority support</li>
            </ul>
            @if(auth()->user()->isPro())
                <span class="pricing-current">Current plan</span>
            @else
                <span class="pricing-current">Active</span>
            @endif
        </article>

        <article class="pricing-card @if(auth()->user()->isPro()) pricing-card-featured @endif">
            <div class="pricing-card-header">
                <span class="pricing-name pricing-name-pro">Pro</span>
                <div class="pricing-price"><strong>$9</strong><small>/month</small></div>
                <p>For committed learners who want the full experience.</p>
            </div>
            <ul class="pricing-features">
                <li><i>✓</i> Everything in Free</li>
                <li><i>✓</i> AI pronunciation (OpenAI TTS)</li>
                <li><i>✓</i> Unlimited vocabulary entries</li>
                <li><i>✓</i> Priority support</li>
                <li><i>✓</i> Early access to new features</li>
            </ul>
            @if(auth()->user()->isPro())
                <form method="POST" action="{{ route('pricing.cancel') }}" onsubmit="return confirm('Downgrade to Free?')">
                    @csrf
                    <button type="submit" class="pricing-action pricing-action-cancel">Cancel subscription</button>
                </form>
            @else
                <form method="POST" action="{{ route('pricing.subscribe') }}">
                    @csrf
                    <button type="submit" class="pricing-action">Subscribe</button>
                </form>
            @endif
            <small class="pricing-note">No payment is processed. This is a demo.</small>
        </article>
    </section>
@endsection
