@extends('admin.layouts.app')

@section('title', 'Settings')
@section('eyebrow', 'Safe configuration view')

@section('content')
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-kicker">Read-only</span>
                <h2>Application status</h2>
            </div>
        </div>

        <div class="admin-settings-grid">
            @foreach($settings as $label => $value)
                <article>
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </article>
            @endforeach
        </div>

        <div class="admin-form-note">
            Secrets such as APP_KEY, database passwords, OpenAI keys, Stripe secrets, and mail passwords are never displayed here.
        </div>
    </section>
@endsection
