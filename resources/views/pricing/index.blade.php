@extends('layouts.app')

@section('title', 'Pricing')

@section('content')
    <section class="pricing-heading">
        <span class="dashboard-date">Simple, transparent pricing</span>
        <h1>Choose the plan<br>that fits your pace.</h1>
        <p>Start free, upgrade when you&rsquo;re ready for more.</p>
    </section>

    <section class="pricing-grid">
        @foreach($plans as $plan)
            <article class="pricing-card @if($currentPlan->id === $plan->id) pricing-card-featured @endif">
                <div class="pricing-card-header">
                    <span class="pricing-name @if($plan->isPremium()) pricing-name-pro @endif">{{ $plan->name }}</span>
                    <div class="pricing-price">
                        @if($plan->price_amount)
                            <strong>${{ number_format($plan->price_amount, 2) }}</strong><small>/{{ $plan->billing_interval }}</small>
                        @else
                            <strong>Free</strong>
                        @endif
                    </div>
                    <p>{{ $plan->description ?? 'Perfect for getting started.' }}</p>
                </div>
                <ul class="pricing-features">
                    @php $limits = $plan->aiLimits @endphp
                    @if($limits)
                        <li><i>{{ $limits->ai_translation_enabled ? '✓' : '✗' }}</i> AI Translation{{ $limits->translations_per_day ? " ({$limits->translations_per_day}/day)" : '' }}</li>
                        <li><i>{{ $limits->ai_explanation_enabled ? '✓' : '✗' }}</i> AI Explanations{{ $limits->explanations_per_day ? " ({$limits->explanations_per_day}/day)" : '' }}</li>
                        <li><i>{{ $limits->ai_tts_enabled ? '✓' : '✗' }}</i> AI Pronunciation (TTS){{ $limits->tts_minutes_per_month ? " ({$limits->tts_minutes_per_month} min/mo)" : '' }}</li>
                        <li><i>{{ $limits->browser_tts_enabled ? '✓' : '✗' }}</i> Browser TTS</li>
                        <li><i>✓</i> Vocabulary with context</li>
                        <li><i>{{ $limits->premium_books_enabled ? '✓' : '✗' }}</i> Premium books</li>
                        <li><i>{{ $limits->private_books_limit !== 0 ? '✓' : '✗' }}</i> {{ $limits->private_books_limit ? "Up to {$limits->private_books_limit}" : 'Unlimited' }} private books</li>
                    @else
                        <li><i>✓</i> Upload your own books (TXT, EPUB)</li>
                        <li><i>✓</i> Built-in reader with translator</li>
                        <li><i>✓</i> Vocabulary with context</li>
                        <li><i>✓</i> Speaking practice (browser-based)</li>
                        <li><i>✗</i> AI pronunciation</li>
                    @endif
                </ul>

                @if($currentPlan->id === $plan->id)
                    <span class="pricing-current">Current plan</span>
                @elseif($plan->isFree())
                    <form method="POST" action="{{ route('pricing.cancel') }}" onsubmit="return confirm('Downgrade to Free?')">
                        @csrf
                        <button type="submit" class="pricing-action pricing-action-cancel">Downgrade to Free</button>
                    </form>
                @elseif($plan->price_amount && $plan->price_amount > 0)
                    <form method="POST" action="{{ route('pricing.subscribe') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="pricing-action">Subscribe</button>
                    </form>
                @endif
            </article>
        @endforeach
    </section>

    @if($plans->where('price_amount', '>', 0)->count())
        <small class="pricing-note">No payment is processed. This is a demo.</small>
    @endif
@endsection
