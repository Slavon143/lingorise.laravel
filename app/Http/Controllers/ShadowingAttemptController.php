<?php

namespace App\Http\Controllers;

use App\Enums\SelfRating;
use App\Http\Requests\ShadowingAttemptRequest;
use App\Models\Book;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Shadowing\ShadowingService;
use Illuminate\Http\JsonResponse;

class ShadowingAttemptController extends Controller
{
    public function __construct(
        private readonly ShadowingService $shadowingService,
        private readonly BookAccessService $bookAccess,
    ) {}

    public function store(ShadowingAttemptRequest $request, Book $book): JsonResponse
    {
        $user = $request->user();

        if (! $this->bookAccess->userCanAccess($user, $book)) {
            abort(403, 'You do not have access to this book.');
        }

        $validated = $request->validated();

        $attempt = $this->shadowingService->recordAttempt(
            user: $user,
            book: $book,
            pageNumber: (int) $validated['page_number'],
            wordIndexStart: (int) $validated['word_index_start'],
            wordIndexEnd: (int) $validated['word_index_end'],
            sentenceHash: $validated['sentence_hash'],
            selfRating: isset($validated['self_rating']) ? SelfRating::from($validated['self_rating']) : null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'attempts_count' => $attempt->attempts_count,
                'self_rating' => $attempt->self_rating?->value,
            ],
        ]);
    }
}
