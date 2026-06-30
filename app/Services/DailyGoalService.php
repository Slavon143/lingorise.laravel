<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class DailyGoalService
{
    public function __construct(private readonly DailyGoalSettingsService $settingsService)
    {
    }

    public function viewModel(User $user): array
    {
        $settings = $this->settingsService->get();
        $timezone = $user->timezone ?? config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $goalMinutes = (int) ($user->daily_goal_minutes ?: $settings['default_minutes']);
        $readMinutesToday = $this->readMinutesForDate($user, $today, $timezone);
        $readSecondsToday = $readMinutesToday * 60;
        $remainingMinutes = max(0, $goalMinutes - $readMinutesToday);
        $progressPercent = $goalMinutes > 0 ? (int) round($readMinutesToday / $goalMinutes * 100) : 0;
        $visualPercent = min(max($progressPercent, 0), 100);
        $lastProgress = $user->readingProgress()
            ->with('book')
            ->whereNotNull('last_read_at')
            ->latest('last_read_at')
            ->first();

        return [
            'goal_minutes' => $goalMinutes,
            'read_seconds_today' => $readSecondsToday,
            'read_minutes_today' => $readMinutesToday,
            'remaining_minutes' => $remainingMinutes,
            'progress_percent' => $progressPercent,
            'visual_percent' => $visualPercent,
            'is_completed' => $readMinutesToday >= $goalMinutes,
            'is_over_goal' => $readMinutesToday > $goalMinutes,
            'current_streak' => $settings['streak_enabled'] ? $this->currentStreak($user, $goalMinutes, $today, $timezone) : 0,
            'has_active_book' => (bool) ($lastProgress?->book),
            'continue_url' => $lastProgress?->book
                ? route('reader.show', ['book' => $lastProgress->book, 'page' => max(1, (int) $lastProgress->current_page)])
                : route('library.index'),
            'settings' => $settings,
        ];
    }

    public function readMinutesForDate(User $user, CarbonImmutable $date, string $timezone): int
    {
        $start = $date->startOfDay()->timezone('UTC');
        $end = $date->endOfDay()->timezone('UTC');

        return $user->readingProgress()
            ->whereNotNull('last_read_at')
            ->whereBetween('last_read_at', [$start, $end])
            ->get(['words_read'])
            ->sum(fn ($progress) => max(0, (int) round($progress->words_read / 200)));
    }

    public function currentStreak(User $user, int $goalMinutes, CarbonImmutable $today, string $timezone): int
    {
        $cursor = $today;
        $todayMinutes = $this->readMinutesForDate($user, $cursor, $timezone);

        if ($todayMinutes < $goalMinutes) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;

        for ($i = 0; $i < 730; $i++) {
            if ($this->readMinutesForDate($user, $cursor, $timezone) < $goalMinutes) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
