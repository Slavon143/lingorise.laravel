<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrammarExplanationRequest;
use App\Models\Book;
use App\Services\Intelligence\Explanation\GrammarExplanationService;
use App\Http\Responses\AiErrorResponse;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Http\JsonResponse;
use Throwable;

class GrammarExplanationController extends Controller
{
    public function __construct(
        private readonly GrammarExplanationService $explanationService,
        private readonly AiQuotaGuard $quotaGuard,
        private readonly BookAccessService $bookAccess,
    ) {}

    public function __invoke(GrammarExplanationRequest $request, Book $book): JsonResponse
    {
        $user = $request->user();

        if (! $this->bookAccess->userCanAccess($user, $book)) {
            abort(403, 'You do not have access to this book.');
        }

        $targetLanguage = $request->input('target_language')
            ?? $user->languagePreference?->native_locale
            ?? 'de';

        $validated = $request->validated();

        try {
            $this->quotaGuard->assertGrammarExplanationAllowed($user);
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorCode?->value ?? 'quota_exceeded',
                'resets_at' => $e->resetsAt?->toIso8601String(),
            ], 429);
        }

        try {
            $result = $this->explanationService->explain(
                text: $validated['text'],
                context: $validated['context'] ?? null,
                sourceLanguage: $validated['source_language'],
                targetLanguage: $targetLanguage,
                userId: $user->id,
                book: $book,
                usageContext: new AiUsageContext(userId: $user->id),
            );

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return AiErrorResponse::fromException($exception, 'Grammar explanation is temporarily unavailable.');
        }
    }
}
