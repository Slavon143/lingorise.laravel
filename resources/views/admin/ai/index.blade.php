@extends('admin.layouts.app')

@section('title', 'AI & TTS')
@section('eyebrow', 'Intelligence layer')

@section('content')
    <section class="admin-placeholder">
        <span class="admin-placeholder-icon">✦</span>
        <h2>AI & TTS module</h2>
        <p>This module will be implemented in the next stage.</p>
        <div class="admin-module-grid">
            @foreach(['Usage', 'Limits', 'Cache', 'TTS', 'Errors'] as $module)
                <article>
                    <strong>{{ $module }}</strong>
                    <small>Prepared for future routes and reports.</small>
                </article>
            @endforeach
        </div>
    </section>
@endsection
