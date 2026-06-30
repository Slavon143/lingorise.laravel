@extends('layouts.app')

@section('title', 'Pricing')

@section('content')
    <section class="pricing-heading">
        <div>
            <span class="dashboard-date">Simple, transparent pricing</span>
            <h1>Choose the plan<br>that fits your pace.</h1>
            <p>Start free, upgrade when you&rsquo;re ready for more reading, pronunciation, and AI help.</p>
        </div>
        <div class="pricing-heading-note">
            <span>7-day trial</span>
            <strong>Cancel anytime</strong>
        </div>
    </section>

    <section class="pricing-grid">
        @foreach($plans as $plan)
            @php
                $reader = $plan->readerSettings;
                $tagline = $plan->isFree()
                    ? 'Start reading and learning for free.'
                    : ($plan->isPremium() ? 'For regular reading and language practice.' : 'For intensive learning and advanced AI tools.');
                $cta = $plan->isFree()
                    ? 'Start free'
                    : ($plan->isPremium() ? 'Upgrade to Premium' : 'Go Pro');
            @endphp
            <article class="pricing-card @if($currentPlan->id === $plan->id) pricing-card-featured @endif">
                <div class="pricing-card-header">
                    <span class="pricing-name @if($plan->isPremium()) pricing-name-pro @endif">{{ $plan->name }}</span>
                    @if($plan->isPremium()) <span class="pricing-popular">Most popular</span> @endif
                    <div class="pricing-price">
                        @if($plan->price_amount)
                            <strong>${{ number_format($plan->price_amount, 2) }}</strong><small>/{{ $plan->billing_interval }}</small>
                        @else
                            <strong>Free</strong>
                        @endif
                    </div>
                    <p>{{ $tagline }}</p>
                </div>
                <ul class="pricing-features">
                    <li><i>✓</i> Translation up to {{ $reader?->translation_max_words ?? 10 }} words</li>
                    <li><i>{{ $reader?->ai_tts_enabled ? '✓' : '✗' }}</i> {{ $reader?->ai_tts_enabled ? 'Natural AI voice' : 'Browser voice only' }}</li>
                    <li><i>✓</i> {{ $reader?->ai_actions_daily_limit ?? 10 }} AI actions/day</li>
                    <li><i>{{ $reader?->shadowing_enabled ? '✓' : '✗' }}</i> Shadowing practice</li>
                    <li><i>✓</i> {{ $reader?->vocabulary_entries_limit ? number_format($reader->vocabulary_entries_limit).' vocabulary entries' : 'Unlimited vocabulary' }}</li>
                    <li><i>✓</i> {{ $reader?->private_books_limit ? 'Up to '.$reader->private_books_limit.' private books' : 'Unlimited private books' }}</li>
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
                        <button type="submit" class="pricing-action">{{ $cta }}</button>
                    </form>
                @endif
            </article>
        @endforeach
    </section>

    <section class="pricing-compare" aria-labelledby="pricing-compare-title">
        <div class="pricing-compare-head">
            <span class="dashboard-date">Compare all features</span>
            <h2 id="pricing-compare-title">Everything important, side by side.</h2>
        </div>
        <div class="pricing-table-wrap">
            <table class="pricing-table">
                <thead>
                    <tr>
                        <th scope="col">Feature</th>
                        @foreach($plans as $plan)
                            <th scope="col" @class(['is-popular' => $plan->isPremium()])>{{ $plan->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrixRows as $group => $rows)
                        <tr class="pricing-table-group"><th colspan="{{ $plans->count() + 1 }}">{{ $group }}</th></tr>
                        @foreach($rows as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                @foreach($plans as $plan)
                                    @php
                                        $value = $plan->readerSettings?->{$row['key']} ?? null;
                                        $display = !empty($row['boolean'])
                                            ? ($value ? 'Included' : 'Not included')
                                            : ($value === null ? 'Unlimited' : number_format($value).($row['suffix'] ?? ''));
                                    @endphp
                                    <td @class(['is-popular' => $plan->isPremium()])>{{ $display }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if($plans->where('price_amount', '>', 0)->count())
        <small class="pricing-note">No payment is processed. This is a demo.</small>
    @endif
@endsection
