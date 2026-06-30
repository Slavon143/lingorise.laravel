<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Http\Requests\UpdateDailyGoalRequest;
use App\Services\DailyGoalService;
use App\Services\ReaderTextFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ReaderTextFormatter $formatter, DailyGoalService $dailyGoalService): View
    {
        $user = $request->user()->load('languagePreference');

        $lastProgress = $user->readingProgress()
            ->with('book')
            ->whereNotNull('last_read_at')
            ->latest('last_read_at')
            ->first();

        $continueBook = null;
        $continuePage = 1;
        $continueTotalPages = 0;
        $continuePercentage = 0;
        $continueReadingTime = null;

        if ($lastProgress && $lastProgress->book) {
            $continueBook = $lastProgress->book;
            $continuePage = $lastProgress->current_page;

            $pages = $formatter->pages($continueBook->content);
            $continueTotalPages = count($pages);
            $continuePercentage = $continueTotalPages > 0 ? (int) round(($continuePage / $continueTotalPages) * 100) : 0;

            $totalWords = $continueBook->total_words ?? array_sum(array_map(
                fn (array $blocks): int => array_sum(array_map(
                    fn (array $block): int => count(preg_split('/\s+/u', trim($block['text']), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                    $blocks,
                )),
                $pages,
            ));
            $wordsRead = $lastProgress->words_read ?? 0;
            $wordsRemaining = max(0, $totalWords - $wordsRead);
            $continueReadingTime = max(1, (int) ceil($wordsRemaining / 200));
        }

        $recentEntries = $user->dictionaryEntries()->count();
        $totalWordsRead = $user->readingProgress()->sum('words_read');

        $dailyGoal = $dailyGoalService->viewModel($user);

        $learningLocale = $user->languagePreference?->learning_locale;

        $languageNames = [
            'en' => 'English', 'de' => 'German', 'es' => 'Spanish',
            'fr' => 'French', 'sv' => 'Swedish',
        ];
        $learningLanguageName = $languageNames[$learningLocale] ?? 'English';

        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $userLevel = $user->books()->whereNotNull('level')->value('level') ?? 'A2';
        $userBookIds = $user->books()->pluck('id');

        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $levelIndex = array_search($userLevel, $levels) ?: 0;
        $priorityLevels = [
            $levels[$levelIndex] ?? null,
            $levels[$levelIndex + 1] ?? null,
            $levels[$levelIndex - 1] ?? null,
        ];

        $recommended = null;
        if ($learningLocale) {
            $query = Book::public()
                ->where('language_locale', $learningLocale)
                ->whereNotIn('id', $userBookIds)
                ->where('total_words', '>', 0)
                ->where('total_words', '<', 5000);
            $recommended = $query->get()->sortBy(function ($book) use ($priorityLevels) {
                $priority = array_search($book->level, $priorityLevels);
                return [$priority === false ? 99 : $priority, $book->total_words];
            })->first();
        }

        return view('dashboard', [
            'user' => $user,
            'preference' => $user->languagePreference,
            'continueBook' => $continueBook,
            'continuePage' => $continuePage,
            'continueTotalPages' => $continueTotalPages,
            'continuePercentage' => $continuePercentage,
            'continueReadingTime' => $continueReadingTime,
            'recentEntries' => $recentEntries,
            'totalWordsRead' => $totalWordsRead,
            'dailyGoal' => $dailyGoal,
            'recommended' => $recommended,
            'greeting' => $greeting,
            'learningLanguageName' => $learningLanguageName,
            'languageNames' => $languageNames,
            'streak' => $dailyGoal['current_streak'],
        ]);
    }

    public function updateDailyGoal(UpdateDailyGoalRequest $request, DailyGoalService $dailyGoalService): RedirectResponse|JsonResponse
    {
        $request->user()->forceFill([
            'daily_goal_minutes' => (int) $request->validated('daily_goal_minutes'),
        ])->save();

        $viewModel = $dailyGoalService->viewModel($request->user()->fresh());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('daily_goal.goal_updated'),
                'daily_goal' => $viewModel,
            ]);
        }

        return back()->with('status', __('daily_goal.goal_updated'));
    }

    public function updateLanguages(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'native_locale' => ['required', 'string', 'max:10', 'different:learning_locale'],
            'learning_locale' => ['required', 'string', 'max:10', 'different:native_locale'],
        ]);

        $request->user()->languagePreference()->updateOrCreate([], $validated);

        return back()->with('status', 'Your language settings have been saved.');
    }
}
