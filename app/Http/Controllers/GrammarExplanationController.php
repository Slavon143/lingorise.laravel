<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrammarExplanationRequest;
use App\Http\Responses\AiErrorResponse;
use App\Models\Book;
use App\Services\Intelligence\Explanation\GrammarExplanationService;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Intelligence\Usage\AiUsageContext;
use App\Services\Plans\ReaderEntitlementService;
use Illuminate\Http\JsonResponse;
use Throwable;

class GrammarExplanationController extends Controller
{
    public function __construct(
        private readonly GrammarExplanationService $explanationService,
        private readonly AiQuotaGuard $quotaGuard,
        private readonly BookAccessService $bookAccess,
        private readonly ReaderEntitlementService $entitlements,
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

        if (! $this->entitlements->isFeatureEnabled($user, 'grammar')) {
            return response()->json([
                'code' => 'feature_not_available',
                'feature' => 'grammar',
                'message' => 'Grammar explanation is not available on your current plan.',
            ], 403);
        }

        $wordLimit = $this->entitlements->validateWordLimit($user, 'grammar', $validated['text']);

        if (! $wordLimit['allowed']) {
            return response()->json([
                'code' => 'word_limit_exceeded',
                'feature' => 'grammar',
                'current_words' => $wordLimit['current_words'],
                'max_words' => $wordLimit['max_words'],
                'plan' => $wordLimit['plan'],
                'upgrade_available' => true,
                'upgrade_url' => route('pricing.index'),
            ], 422);
        }

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
