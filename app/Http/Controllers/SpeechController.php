<?php

namespace App\Http\Controllers;

use App\Services\Ai\TtsCacheService;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SpeechController extends Controller
{
    public function __invoke(Request $request, TtsCacheService $tts, AiQuotaGuard $quotaGuard): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $user = $request->user();

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
                config('services.openai.tts_voice', 'marin'),
                config('services.openai.tts_model', 'gpt-4o-mini-tts'),
                'mp3',
                '1',
            );

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
