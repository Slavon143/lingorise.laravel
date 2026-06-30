<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimplificationRequest;
use App\Models\Book;
use App\Services\Intelligence\Explanation\SimplificationService;
use App\Http\Responses\AiErrorResponse;
use App\Services\Intelligence\Subscription\AiQuotaExceededException;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Http\JsonResponse;
use Throwable;

class SimplificationController extends Controller
{
    public function __construct(
        private readonly SimplificationService $simplificationService,
        private readonly AiQuotaGuard $quotaGuard,
        private readonly BookAccessService $bookAccess,
    ) {}

    public function __invoke(SimplificationRequest $request, Book $book): JsonResponse
    {
        $user = $request->user();

        if (! $this->bookAccess->userCanAccess($user, $book)) {
            abort(403, 'You do not have access to this book.');
        }

        $validated = $request->validated();

        try {
            $this->quotaGuard->assertSimplificationAllowed($user);
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorCode?->value ?? 'quota_exceeded',
                'resets_at' => $e->resetsAt?->toIso8601String(),
            ], 429);
        }

        try {
            $result = $this->simplificationService->simplify(
                text: $validated['text'],
                sourceLanguage: $validated['source_language'],
                targetLevel: $validated['target_level'],
                preserveStyle: $validated['preserve_style'] ?? false,
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

            return AiErrorResponse::fromException($exception, 'Simplification is temporarily unavailable.');
        }
    }
}
