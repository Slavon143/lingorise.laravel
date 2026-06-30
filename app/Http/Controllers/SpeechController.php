<?php

namespace App\Http\Controllers;

use App\Services\Ai\TtsCacheService;
use App\Services\Intelligence\Cache\TtsCacheRepository;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SpeechController extends Controller
{
    public function __invoke(Request $request, TtsCacheService $tts, AiQuotaGuard $quotaGuard, TtsCacheRepository $ttsCache): Response
    {
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

        if ($cached = $ttsCache->findUsable($cacheKey)) {
            return $this->audioResponse(
                Storage::disk('local')->get($cached->file_path),
                $cacheKey,
                true,
            );
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
            'ETag' => '"' . $cacheKey . '"',
            'X-AI-Cache' => $cacheHit ? 'HIT' : 'MISS',
        ]);
    }
}
