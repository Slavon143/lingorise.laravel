<?php

namespace App\Http\Controllers;

use App\Services\Ai\TtsCacheService;
use App\Services\Intelligence\Cache\TtsCacheRepository;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Plans\ReaderEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SpeechController extends Controller
{
    public function __invoke(
        Request $request,
        TtsCacheService $tts,
        AiQuotaGuard $quotaGuard,
        TtsCacheRepository $ttsCache,
        ReaderEntitlementService $entitlements,
    ): Response {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $user = $request->user();
        $voice = config('services.openai.tts_voice', 'marin');
        $model = config('services.openai.tts_model', 'gpt-4o-mini-tts');
        $format = 'mp3';
        $speed = '1';
        $cacheKey = $ttsCache->cacheKey(
            $validated['text'],
            $validated['locale'] ?? 'en',
            'openai',
            $model,
            $voice,
            $speed,
            $format,
        );

        $wordLimit = $entitlements->validateWordLimit($user, 'tts', $validated['text']);

        if (! $wordLimit['allowed']) {
            return response()->json([
                'code' => 'word_limit_exceeded',
                'feature' => 'tts',
                'current_words' => $wordLimit['current_words'],
                'max_words' => $wordLimit['max_words'],
                'plan' => $wordLimit['plan'],
                'fallback' => $entitlements->canUseBrowserTts($user) ? 'browser_tts' : null,
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 422);
        }

        if (! $entitlements->isFeatureEnabled($user, 'ai_tts')) {
            return response()->json([
                'code' => 'ai_tts_not_available',
                'message' => 'AI voice is not available on your current plan.',
                'fallback' => $entitlements->canUseBrowserTts($user) ? 'browser_tts' : null,
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 403);
        }

        if ($cached = $ttsCache->findUsable($cacheKey)) {
            return $this->audioResponse(
                Storage::disk('local')->get($cached->file_path),
                $cacheKey,
                true,
            );
        }

        if (! $entitlements->canUseAiTts($user)) {
            return response()->json([
                'code' => 'tts_character_limit_reached',
                'message' => 'Your monthly AI voice character limit has been reached.',
                'fallback' => $entitlements->canUseBrowserTts($user) ? 'browser_tts' : null,
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 403);
        }

        try {
            $quotaGuard->assertTtsAllowed($user);
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'upgrade_url' => route('pricing.index'),
                'resets_at' => $e->resetsAt?->toIso8601String(),
            ], 403);
        }

        abort_unless(config('services.openai.key'), 503, 'Natural voice is not configured.');

        try {
            $audio = $tts->audio(
                $validated['text'],
                $validated['locale'] ?? 'en',
                $user->id,
                $voice,
                $model,
                $format,
                $speed,
            );

            return $this->audioResponse($audio['body'], $audio['cache_key'], $audio['cache_hit']);
        } catch (Throwable $exception) {
            report($exception);

            abort(503, 'Natural voice is temporarily unavailable.');
        }
    }

    private function audioResponse(string $body, string $cacheKey, bool $cacheHit): Response
    {
        return response($body, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) strlen($body),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => '"'.$cacheKey.'"',
            'X-AI-Cache' => $cacheHit ? 'HIT' : 'MISS',
        ]);
    }
}
