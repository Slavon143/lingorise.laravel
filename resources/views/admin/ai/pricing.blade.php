@extends('admin.layouts.app')

@section('title', 'AI pricing')
@section('eyebrow', 'AI & TTS')

@section('content')
    @foreach($pricing as $provider => $config)
        @if($provider === 'usd_to_sek_rate' || $provider === 'exchange_rate_updated_at' || $provider === 'ai_enabled' || $provider === 'translation_enabled' || $provider === 'explanation_enabled' || $provider === 'tts_enabled' || $provider === 'browser_tts_fallback_enabled' || $provider === 'monthly_budget_usd' || $provider === 'warning_threshold_percent' || $provider === 'hard_stop_threshold_percent')
            @continue
        @endif
        <h3 style="margin: 24px 0 12px;">{{ ucfirst($provider) }}</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Effective from</th>
                    <th>Pricing version</th>
                    <th>Input (per M tokens)</th>
                    <th>Cached input (per M)</th>
                    <th>Output (per M)</th>
                    <th>Currency</th>
                </tr>
            </thead>
            <tbody>
                @foreach($config['models'] ?? [] as $model => $p)
                    <tr>
                        <td><strong>{{ $model }}</strong></td>
                        <td>{{ $p['effective_from'] ?? '—' }}</td>
                        <td>{{ $p['pricing_version'] ?? '—' }}</td>
                        <td>{{ $p['input_per_million_tokens'] !== null ? '$' . number_format($p['input_per_million_tokens'], 4) : 'N/A' }}</td>
                        <td>{{ $p['cached_input_per_million_tokens'] !== null ? '$' . number_format($p['cached_input_per_million_tokens'], 4) : 'N/A' }}</td>
                        <td>{{ $p['output_per_million_tokens'] !== null ? '$' . number_format($p['output_per_million_tokens'], 4) : 'N/A' }}</td>
                        <td>{{ $p['currency'] ?? 'USD' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <p style="margin-top: 20px; color: var(--muted); font-size: .85rem;">
        Pricing is read-only. Prices are based on official OpenAI pricing as of the effective_from date.
        Actual costs may vary. Estimated costs are not exact billing amounts.
    </p>
@endsection
