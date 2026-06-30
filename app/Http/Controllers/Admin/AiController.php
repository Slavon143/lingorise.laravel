<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageEvent;
use App\Models\ExplanationCache;
use App\Models\TtsCache;
use App\Models\TranslationCache;
use App\Services\Intelligence\Budget\AiBudgetGuard;
use App\Services\Intelligence\Cost\ExchangeRateService;
use App\Services\Intelligence\Usage\AiUsageAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AiController extends Controller
{
    public function __construct(
        private readonly AiUsageAggregator $aggregator,
        private readonly AiBudgetGuard $budget,
        private readonly ExchangeRateService $exchange,
    ) {}

    public function index(Request $request): View
    {
        $period = $request->query('period', 'today');
        $dates = $this->parsePeriod($period);

        $overview = $this->aggregator->overview($dates['from'], $dates['to']);
        $dailyStats = $this->aggregator->dailyStats($dates['from'], $dates['to']);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfDay();
        $monthly = $this->aggregator->overview($monthStart, $monthEnd);

        $ttsStorageSize = 0;
        $ttsFileCount = TtsCache::count();
        if ($ttsFileCount > 0) {
            $path = Storage::disk('local')->path('private/tts');
            if (is_dir($path)) {
                $ttsStorageSize = $this->dirSize($path);
            }
        }

        $overview['tts_minutes'] = $monthly['tts_duration_ms'] > 0
            ? round($monthly['tts_duration_ms'] / 60000, 1)
            : 0;

        $rate = $this->exchange->getRate();

        return view('admin.ai.index', compact(
            'overview', 'dailyStats', 'monthly', 'period',
            'ttsFileCount', 'ttsStorageSize', 'rate',
        ));
    }

    public function usage(Request $request): View
    {
        $query = AiUsageEvent::query();

        if ($request->filled('period')) {
            $dates = $this->parsePeriod($request->query('period'));
            $query->whereBetween('created_at', [$dates['from'], $dates['to']]);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->query('book_id'));
        }

        if ($request->filled('operation')) {
            $query->where('operation_type', $request->query('operation'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('cache_hit')) {
            $query->where('cache_hit', $request->boolean('cache_hit'));
        }

        if ($request->filled('min_cost')) {
            $query->where('estimated_cost_usd', '>=', (float) $request->query('min_cost'));
        }

        if ($request->boolean('only_errors')) {
            $query->whereIn('status', ['failed', 'rate_limited']);
        }

        $events = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        return view('admin.ai.usage', compact('events'));
    }

    public function usageShow(AiUsageEvent $event): View
    {
        $event->load(['user', 'book']);

        return view('admin.ai.usage-show', compact('event'));
    }

    public function users(Request $request): View
    {
        $dates = $this->parsePeriod($request->query('period', 'month'));
        $users = $this->aggregator->costByUser($dates['from'], $dates['to']);

        return view('admin.ai.users', compact('users'));
    }

    public function books(Request $request): View
    {
        $dates = $this->parsePeriod($request->query('period', 'month'));
        $books = $this->aggregator->costByBook($dates['from'], $dates['to']);

        return view('admin.ai.books', compact('books'));
    }

    public function cacheTranslations(Request $request): View
    {
        $query = TranslationCache::query();

        if ($request->filled('q')) {
            $term = $request->query('q');
            $query->where(function ($q) use ($term): void {
                $q->where('source_text', 'like', "%{$term}%")
                  ->orWhere('translated_text', 'like', "%{$term}%");
            });
        }

        $entries = $query->orderByDesc('last_used_at')->paginate(30)->withQueryString();

        return view('admin.ai.cache-translations', compact('entries'));
    }

    public function cacheTranslationShow(TranslationCache $translationCache): View
    {
        return view('admin.ai.cache-translation-show', ['entry' => $translationCache]);
    }

    public function cacheTranslationDestroy(TranslationCache $translationCache): \Illuminate\Http\RedirectResponse
    {
        $translationCache->delete();

        return redirect()->route('admin.ai.cache.translations.index')
            ->with('status', 'Translation cache entry deleted.');
    }

    public function cacheExplanations(Request $request): View
    {
        $query = ExplanationCache::query();

        if ($request->filled('q')) {
            $term = $request->query('q');
            $query->where(function ($q) use ($term): void {
                $q->where('selected_text', 'like', "%{$term}%")
                  ->orWhere('explanation_text', 'like', "%{$term}%");
            });
        }

        $entries = $query->orderByDesc('last_used_at')->paginate(30)->withQueryString();

        return view('admin.ai.cache-explanations', compact('entries'));
    }

    public function cacheExplanationShow(ExplanationCache $explanationCache): View
    {
        return view('admin.ai.cache-explanation-show', ['entry' => $explanationCache]);
    }

    public function cacheExplanationDestroy(ExplanationCache $explanationCache): \Illuminate\Http\RedirectResponse
    {
        $explanationCache->delete();

        return redirect()->route('admin.ai.cache.explanations.index')
            ->with('status', 'Explanation cache entry deleted.');
    }

    public function cacheTts(Request $request): View
    {
        $query = TtsCache::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $entries = $query->orderByDesc('last_used_at')->paginate(30)->withQueryString();

        return view('admin.ai.cache-tts', compact('entries'));
    }

    public function cacheTtsShow(TtsCache $ttsCache): View
    {
        return view('admin.ai.cache-tts-show', compact('ttsCache'));
    }

    public function cacheTtsDestroy(TtsCache $ttsCache): \Illuminate\Http\RedirectResponse
    {
        $path = $ttsCache->file_path;
        $ttsCache->delete();

        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('admin.ai.cache.tts.index')
            ->with('status', 'TTS cache entry and file deleted.');
    }

    public function errors(Request $request): View
    {
        $query = AiUsageEvent::whereIn('status', ['failed', 'rate_limited']);

        if ($request->filled('period')) {
            $dates = $this->parsePeriod($request->query('period'));
            $query->whereBetween('created_at', [$dates['from'], $dates['to']]);
        }

        if ($request->filled('operation')) {
            $query->where('operation_type', $request->query('operation'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        $events = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        return view('admin.ai.errors', compact('events'));
    }

    public function pricing(): View
    {
        $pricing = config('ai_pricing');

        return view('admin.ai.pricing', compact('pricing'));
    }

    /**
     * @return array{from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon}
     */
    private function parsePeriod(string $period): array
    {
        return match ($period) {
            'yesterday' => [
                'from' => now()->subDay()->startOfDay(),
                'to' => now()->subDay()->endOfDay(),
            ],
            'week' => [
                'from' => now()->startOfWeek(),
                'to' => now()->endOfDay(),
            ],
            'month' => [
                'from' => now()->startOfMonth(),
                'to' => now()->endOfDay(),
            ],
            'prev-month' => [
                'from' => now()->subMonth()->startOfMonth(),
                'to' => now()->subMonth()->endOfMonth(),
            ],
            default => [
                'from' => now()->startOfDay(),
                'to' => now()->endOfDay(),
            ],
        };
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
