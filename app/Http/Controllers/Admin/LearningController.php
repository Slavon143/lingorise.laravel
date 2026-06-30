<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiStructuredCache;
use App\Models\ShadowingAttempt;
use App\Models\UserWord;
use App\Models\UserWordEvent;
use App\Services\Intelligence\Usage\AiUsageAggregator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function __construct(
        private readonly AiUsageAggregator $aggregator,
    ) {}

    public function index(): View
    {
        $totalWords = UserWord::count();
        $totalEvents = UserWordEvent::count();
        $totalShadowing = ShadowingAttempt::count();
        $totalCache = AiStructuredCache::count();

        $statusBreakdown = UserWord::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentEvents = UserWordEvent::with(['user', 'word'])
            ->latest()
            ->take(20)
            ->get();

        return view('admin.learning.index', compact(
            'totalWords', 'totalEvents', 'totalShadowing', 'totalCache',
            'statusBreakdown', 'recentEvents',
        ));
    }

    public function words(Request $request): View
    {
        $query = UserWord::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('language')) {
            $query->where('language', $request->query('language'));
        }

        if ($request->filled('search')) {
            $q = $request->query('search');
            $query->where(function ($b) use ($q) {
                $b->where('word', 'like', "%{$q}%")
                  ->orWhere('lemma', 'like', "%{$q}%");
            });
        }

        $words = $query->latest()->paginate(50);

        return view('admin.learning.words', compact('words'));
    }

    public function shadowing(Request $request): View
    {
        $query = ShadowingAttempt::with(['user', 'book']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->query('book_id'));
        }

        $attempts = $query->latest()->paginate(50);

        return view('admin.learning.shadowing', compact('attempts'));
    }

    public function cache(Request $request): View
    {
        $query = AiStructuredCache::query();

        if ($request->filled('operation_type')) {
            $query->where('operation_type', $request->query('operation_type'));
        }

        $entries = $query->latest()->paginate(50);

        $operationTypes = AiStructuredCache::select('operation_type')
            ->distinct()->pluck('operation_type');

        return view('admin.learning.cache', compact('entries', 'operationTypes'));
    }

    public function events(Request $request): View
    {
        $query = UserWordEvent::with(['user', 'word']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->query('event_type'));
        }

        $events = $query->latest()->paginate(50);

        $eventTypes = UserWordEvent::select('event_type')
            ->distinct()->pluck('event_type');

        return view('admin.learning.events', compact('events', 'eventTypes'));
    }
}
