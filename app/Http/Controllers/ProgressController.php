<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $totalBooks = $user->books()->count();
        $completedBooks = $user->readingProgress()->whereNotNull('completed_at')->count();
        $totalWordsRead = $user->readingProgress()->sum('words_read');
        $totalEntries = $user->dictionaryEntries()->count();
        $favoriteEntries = $user->dictionaryEntries()->where('is_favorite', true)->count();

        $readingMinutes = $totalWordsRead > 0 ? max(1, (int) round($totalWordsRead / 200)) : 0;

        $streakDates = $user->readingProgress()
            ->whereNotNull('last_read_at')
            ->selectRaw('DATE(last_read_at) as read_date')
            ->distinct()
            ->pluck('read_date')
            ->sort()
            ->reverse()
            ->values();

        $streak = 0;
        $checkDate = today()->toDateString();
        if ($streakDates->isNotEmpty()) {
            if ($streakDates->first() === $checkDate || $streakDates->first() === today()->subDay()->toDateString()) {
                if ($streakDates->first() !== $checkDate) {
                    $checkDate = today()->subDay()->toDateString();
                }
                foreach ($streakDates as $date) {
                    if ($date === $checkDate) {
                        $streak++;
                        $checkDate = \Carbon\Carbon::parse($checkDate)->subDay()->toDateString();
                    } elseif ($date < $checkDate) {
                        break;
                    }
                }
            }
        }

        $allProgress = $user->readingProgress()
            ->whereNotNull('last_read_at')
            ->orderBy('book_id')
            ->orderBy('last_read_at')
            ->get(['book_id', 'words_read', 'last_read_at']);

        $dailyDeltas = [];
        $prevWords = [];
        foreach ($allProgress as $p) {
            $date = $p->last_read_at->toDateString();
            $prev = $prevWords[$p->book_id] ?? 0;
            $delta = max(0, $p->words_read - $prev);
            $dailyDeltas[$date] = ($dailyDeltas[$date] ?? 0) + $delta;
            $prevWords[$p->book_id] = $p->words_read;
        }

        $dates = array_keys($dailyDeltas);
        $labels = collect($dates)->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M j'))->toJson();
        $data = collect($dates)->map(fn ($d) => (int) $dailyDeltas[$d])->toJson();

        $topBooks = $user->readingProgress()
            ->with('book:id,title')
            ->selectRaw('book_id, MAX(words_read) as words')
            ->groupBy('book_id')
            ->orderByDesc('words')
            ->limit(5)
            ->get();

        $dailyMinutes = $user->readingProgress()
            ->whereDate('updated_at', today())
            ->get()
            ->sum(fn ($p) => max(0, (int) round($p->words_read / 200)));

        return view('progress.index', compact(
            'totalBooks', 'completedBooks', 'totalWordsRead', 'totalEntries',
            'favoriteEntries', 'readingMinutes', 'streak', 'labels', 'data', 'topBooks',
            'dailyMinutes',
        ));
    }
}
