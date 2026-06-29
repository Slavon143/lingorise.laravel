<?php

namespace App\Http\Controllers;

use App\Services\Ai\TtsCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SpeechController extends Controller
{
    protected int $dailyFreeLimit = 10;

    public function __invoke(Request $request, TtsCacheService $tts): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $user = $request->user();

        if (! $user->isPro()) {
            $cacheKey = 'daily-translations:'.$user->id.':'.now()->toDateString();
            $count = (int) Cache::get($cacheKey, 0);

            if ($count >= $this->dailyFreeLimit) {
                return response()->json([
                    'message' => 'Daily free limit of '.$this->dailyFreeLimit.' AI actions reached. Upgrade to Pro for unlimited practice.',
                    'upgrade_url' => route('pricing.index'),
                ], 403);
            }
        }

        abort_unless(config('services.openai.key'), 503, 'Natural voice is not configured.');

        try {
            $audio = $tts->audio(
                $validated['text'],
                $validated['locale'] ?? 'en',
                $user->id,
                config('services.openai.tts_voice', 'marin'),
                config('services.openai.tts_model', 'gpt-4o-mini-tts'),
                'mp3',
                '1',
            );

            if (! $user->isPro()) {
                Cache::add($cacheKey, 0, now()->endOfDay());
                Cache::increment($cacheKey);
            }

            return response($audio['body'], 200, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, max-age=86400',
                'X-AI-Cache' => $audio['cache_hit'] ? 'HIT' : 'MISS',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            abort(503, 'Natural voice is temporarily unavailable.');
        }
    }
}
