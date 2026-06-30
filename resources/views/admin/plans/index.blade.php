@extends('admin.layouts.app')

@section('title', 'Plans')
@section('eyebrow', 'Plans & subscription')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Tiers</span>
                <h2>Subscription plans</h2>
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Default</th>
                        <th>Active</th>
                        <th>Position</th>
                        <th>Limits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td><code>{{ $plan->code }}</code></td>
                            <td>{{ $plan->name }}</td>
                            <td>
                                @if($plan->price_amount)
                                    ${{ number_format($plan->price_amount, 2) }}/{{ $plan->billing_interval }}
                                @else
                                    Free
                                @endif
                            </td>
                            <td>@if($plan->is_default)<span class="admin-badge">Default</span>@endif</td>
                            <td>@if($plan->is_active)<span class="admin-badge is-admin">Active</span>@endif</td>
                            <td>{{ $plan->position }}</td>
                            <td>
                                @if($plan->aiLimits)
                                    <span title="Translations: {{ $plan->aiLimits->translations_per_day ?? '∞' }}/day, TTS: {{ $plan->aiLimits->tts_minutes_per_month ?? '∞' }} min/mo">
                                        {{ $plan->aiLimits->translations_per_day ?? '∞' }}/d · {{ $plan->aiLimits->tts_minutes_per_month ?? '∞' }} min
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.plans.edit', $plan) }}">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-empty-cell">No plans defined.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-note">
            <strong>Note:</strong> Plans are seeded via <code>PlanSeeder</code>. New plans must be added via seeder or directly in the database.
        </div>
    </section>
@endsection
