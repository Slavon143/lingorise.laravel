<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Plans\ReaderEntitlementService;
use App\Services\WordTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WordTranslationController extends Controller
{
    public function __invoke(
        Request $request,
        Book $book,
        WordTranslationService $translator,
        AiQuotaGuard $quotaGuard,
        ReaderEntitlementService $entitlements,
    ): JsonResponse {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $validated = $request->validate([
            'word' => ['required', 'string', 'max:255'],
            'context' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if (! $entitlements->isFeatureEnabled($user, 'translation')) {
            return response()->json([
                'code' => 'feature_not_available',
                'feature' => 'translation',
                'message' => 'Translation is not available on your current plan.',
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 403);
        }

        $wordLimit = $entitlements->validateWordLimit($user, 'translation', $validated['word']);

        if (! $wordLimit['allowed']) {
            return response()->json([
                'code' => 'word_limit_exceeded',
                'feature' => 'translation',
                'current_words' => $wordLimit['current_words'],
                'max_words' => $wordLimit['max_words'],
                'plan' => $wordLimit['plan'],
                'message' => 'You can translate up to '.$wordLimit['max_words'].' words at once.',
                'errors' => [
                    'word' => ['You can translate up to '.$wordLimit['max_words'].' words at once.'],
                ],
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 422);
        }

        try {
            $quotaGuard->assertTranslationAllowed($user);
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'saved' => false,
                'upgrade_url' => route('pricing.index'),
                'resets_at' => $e->resetsAt?->toIso8601String(),
            ], 403);
        }

        try {
            $result = $translator->translate(
                $validated['word'],
                $validated['context'],
                $book->language_locale ?: 'en',
                $user->languagePreference?->native_locale ?? 'de',
                $user->id,
                $book->isPublic() ? 'public' : 'book',
                $book->isPublic() ? null : $book->id,
            );

            return response()->json($result);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => config('services.openai.key')
                    ? 'Translation is temporarily unavailable.'
                    : 'Automatic translation is not configured.',
            ], 503);
        }
    }
}
