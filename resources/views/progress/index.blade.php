@extends('layouts.app')

@section('title', 'Progress')

@section('content')
    <section class="progress-page-heading">
        <div>
            <span class="dashboard-date">Your learning journey</span>
            <h1>Progress</h1>
            <p>Every word brings you closer. Here&rsquo;s what you&rsquo;ve achieved so far.</p>
        </div>
    </section>

    <section class="progress-stats">
        <article class="stat-card">
            <span class="stat-icon stat-icon-blue">
                <svg viewBox="0 0 22 22" fill="none"><path d="M4 18V9m5 9V4m5 14v-6m5 6V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </span>
            <div><small>Words read</small><strong>{{ number_format($totalWordsRead) }}</strong><span>Across all books</span></div>
        </article>
        <article class="stat-card">
            <span class="stat-icon stat-icon-green">
                <svg viewBox="0 0 22 22" fill="none"><path d="M12 4v10m5-7v7M7 9v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="3" y="14" width="16" height="4" rx="1" stroke="currentColor" stroke-width="1.6"/></svg>
            </span>
            <div><small>Reading time</small><strong>{{ $readingMinutes }}</strong><span>{{ Str::plural('minute', $readingMinutes) }} total</span></div>
        </article>
        <article class="stat-card">
            <span class="stat-icon stat-icon-coral">
                <svg viewBox="0 0 22 22" fill="none"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H18v14H7.5A2.5 2.5 0 0 0 5 19.5v-14Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7h6M8 10h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </span>
            <div><small>Vocabulary</small><strong>{{ $totalEntries }}</strong><span>{{ $favoriteEntries > 0 ? $favoriteEntries . ' favourited' : 'Saved words' }}</span></div>
        </article>
        <article class="stat-card">
            <span class="stat-icon stat-icon-blue">
                <svg viewBox="0 0 22 22" fill="none"><path d="M4 10.5 11 4l7 6.5V18a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            </span>
            <div><small>Books</small><strong>{{ $completedBooks }}/{{ $totalBooks }}</strong><span>{{ $completedBooks === 1 ? 'Completed' : ($completedBooks > 0 ? 'Completed' : 'In progress') }}</span></div>
        </article>
        <article class="stat-card">
            <span class="stat-icon stat-icon-green">
                <svg viewBox="0 0 22 22" fill="none"><path d="M3 12h2l2 4 3-8 2 6 2-4 2 2h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div><small>Best streak</small><strong>{{ $streak }} days</strong><span>{{ $streak > 0 ? 'Current streak' : 'Start reading daily' }}</span></div>
        </article>
        <article class="stat-card">
            <span class="stat-icon stat-icon-coral">
                <svg viewBox="0 0 22 22" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M11 7v4l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </span>
            <div><small>Today</small><strong>{{ $dailyMinutes ?? 0 }} min</strong><span>{{ $streak > 0 ? 'Streak alive' : 'Read today to start' }}</span></div>
        </article>
    </section>

    @if($totalWordsRead > 0)
        <section class="progress-chart-section">
            <article class="progress-chart-card">
                <div class="card-heading"><div><span>Words over time</span><h2>Your reading volume</h2></div></div>
                <div class="progress-chart-wrap">
                    <canvas id="progressChart" data-labels="{{ $labels }}" data-values="{{ $data }}"></canvas>
                </div>
            </article>

            @if($topBooks->isNotEmpty())
                <article class="progress-books-card">
                    <div class="card-heading"><div><span>Most read</span><h2>Your top books</h2></div></div>
                    <ul class="progress-book-list">
                        @foreach($topBooks as $pb)
                            <li>
                                <span>{{ $loop->iteration }}</span>
                                <strong>{{ $pb->book?->title ?? 'Deleted book' }}</strong>
                                <small>{{ number_format($pb->words) }} words</small>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif
        </section>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('progressChart');
    if (!canvas) return;
    try {
        const labels = JSON.parse(canvas.dataset.labels);
        const values = JSON.parse(canvas.dataset.values);
        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: '#4666ff',
                    backgroundColor: 'rgba(70,102,255,.08)',
                    fill: true,
                    tension: .35,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, maxTicksLimit: 10, color: '#8a90a0' },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(17,27,61,.06)' },
                        ticks: { font: { size: 10 }, color: '#8a90a0' },
                    },
                },
            },
        });
    } catch {}
});
</script>
@endpush
