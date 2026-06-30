<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContextExplanationRequest;
use App\Models\Book;
use App\Services\Intelligence\Explanation\ContextExplanationService;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Http\JsonResponse;
use Throwable;

class ContextExplanationController extends Controller
{
    public function __construct(
        private readonly ContextExplanationService $explanationService,
        private readonly AiQuotaGuard $quotaGuard,
        private readonly BookAccessService $bookAccess,
    ) {}

    public function __invoke(ContextExplanationRequest $request, Book $book): JsonResponse
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
            $this->quotaGuard->assertContextExplanationAllowed($user);
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorCode?->value ?? 'quota_exceeded',
                'resets_at' => $e->resetsAt?->toIso8601String(),
            ], 429);
        }

        try {
            $result = $this->explanationService->explain(
                selectedText: $validated['selected_text'],
                context: $validated['context'],
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

            return response()->json([
                'success' => false,
                'message' => 'Context explanation is temporarily unavailable.',
                'error' => 'service_unavailable',
            ], 503);
        }
    }
}
