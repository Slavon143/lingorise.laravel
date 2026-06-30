<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\DailyGoalSettingsService;
use App\Services\DailyGoalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_goal_zero_progress(): void
    {
        $user = User::factory()->create();

        $goal = app(DailyGoalService::class)->viewModel($user);

        $this->assertSame(0, $goal['read_minutes_today']);
        $this->assertSame(10, $goal['goal_minutes']);
        $this->assertFalse($goal['is_completed']);
        $this->assertSame(10, $goal['remaining_minutes']);
    }

    public function test_daily_goal_partial_completed_and_over_goal_states(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create();
        $user->readingProgress()->create([
            'book_id' => $book->id,
            'words_read' => 1200,
            'current_page' => 1,
            'last_read_at' => now(),
        ]);

        $goal = app(DailyGoalService::class)->viewModel($user);
        $this->assertSame(6, $goal['read_minutes_today']);
        $this->assertSame(4, $goal['remaining_minutes']);
        $this->assertSame(60, $goal['progress_percent']);

        $user->readingProgress()->where('book_id', $book->id)->update(['words_read' => 2000]);
        $goal = app(DailyGoalService::class)->viewModel($user->fresh());
        $this->assertTrue($goal['is_completed']);
        $this->assertSame(100, $goal['visual_percent']);

        $user->readingProgress()->where('book_id', $book->id)->update(['words_read' => 3600]);
        $goal = app(DailyGoalService::class)->viewModel($user->fresh());
        $this->assertSame(18, $goal['read_minutes_today']);
        $this->assertSame(180, $goal['progress_percent']);
        $this->assertSame(100, $goal['visual_percent']);
        $this->assertTrue($goal['is_over_goal']);
    }

    public function test_user_can_update_goal_and_invalid_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('settings.daily-goal'), ['daily_goal_minutes' => 20])
            ->assertOk()
            ->assertJsonPath('daily_goal.goal_minutes', 20);

        $this->assertSame(20, $user->fresh()->daily_goal_minutes);

        $this->actingAs($user)->putJson(route('settings.daily-goal'), ['daily_goal_minutes' => 0])->assertUnprocessable();
        $this->actingAs($user)->putJson(route('settings.daily-goal'), ['daily_goal_minutes' => -3])->assertUnprocessable();
        $this->actingAs($user)->putJson(route('settings.daily-goal'), ['daily_goal_minutes' => 181])->assertUnprocessable();
    }

    public function test_streak_counts_goal_completed_days_idempotently(): void
    {
        $user = User::factory()->create();
        foreach ([0, 1, 2] as $daysAgo) {
            $book = Book::factory()->for($user, 'owner')->create();
            $user->readingProgress()->create([
                'book_id' => $book->id,
                'words_read' => 2000,
                'current_page' => 1,
                'last_read_at' => now()->subDays($daysAgo),
            ]);
        }

        $goal = app(DailyGoalService::class)->viewModel($user);

        $this->assertSame(3, $goal['current_streak']);
        $this->assertSame(3, app(DailyGoalService::class)->viewModel($user)['current_streak']);
    }

    public function test_unfinished_today_does_not_break_yesterday_streak(): void
    {
        $user = User::factory()->create();
        $todayBook = Book::factory()->for($user, 'owner')->create();
        $yesterdayBook = Book::factory()->for($user, 'owner')->create();

        $user->readingProgress()->create([
            'book_id' => $todayBook->id,
            'words_read' => 400,
            'current_page' => 1,
            'last_read_at' => now(),
        ]);
        $user->readingProgress()->create([
            'book_id' => $yesterdayBook->id,
            'words_read' => 2000,
            'current_page' => 1,
            'last_read_at' => now()->subDay(),
        ]);

        $this->assertSame(1, app(DailyGoalService::class)->viewModel($user)['current_streak']);
    }

    public function test_continue_url_uses_last_progress_or_library(): void
    {
        $user = User::factory()->create();
        $this->assertSame(route('library.index'), app(DailyGoalService::class)->viewModel($user)['continue_url']);

        $book = Book::factory()->for($user, 'owner')->create();
        $user->readingProgress()->create([
            'book_id' => $book->id,
            'words_read' => 200,
            'current_page' => 3,
            'last_read_at' => now(),
        ]);

        $this->assertSame(route('reader.show', ['book' => $book, 'page' => 3]), app(DailyGoalService::class)->viewModel($user->fresh())['continue_url']);
    }

    public function test_admin_can_update_daily_goal_settings_and_non_admin_cannot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $payload = [
            'default_minutes' => 15,
            'minimum_minutes' => 1,
            'maximum_minutes' => 120,
            'preset_minutes' => '5, 15, 30',
            'streak_enabled' => '1',
            'over_goal_message_enabled' => '1',
            'custom_goal_enabled' => '1',
        ];

        $this->actingAs($admin)
            ->patch(route('admin.settings.daily-goal.update'), $payload)
            ->assertRedirect();

        $this->assertSame([5, 15, 30], app(DailyGoalSettingsService::class)->get()['preset_minutes']);

        $this->actingAs($user)
            ->patch(route('admin.settings.daily-goal.update'), $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.settings.daily-goal.update'), array_merge($payload, ['preset_minutes' => '5, 500']))
            ->assertSessionHasErrors('preset_minutes');
    }
}
