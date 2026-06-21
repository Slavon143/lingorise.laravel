<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\WordTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WordTranslationController extends Controller
{
    public function __invoke(Request $request, Book $book, WordTranslationService $translator): JsonResponse
    {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $validated = $request->validate([
            'word' => ['required', 'string', 'max:255'],
            'context' => ['required', 'string', 'max:1000'],
        ]);

        if (count(preg_split('/\s+/u', trim($validated['word']), -1, PREG_SPLIT_NO_EMPTY) ?: []) > 10) {
            return response()->json([
                'message' => 'You can translate up to 10 words at once.',
                'errors' => [
                    'word' => ['You can translate up to 10 words at once.'],
                ],
            ], 422);
        }

        try {
            return response()->json($translator->translate(
                $validated['word'],
                $validated['context'],
                $book->language_locale ?: 'en',
                $request->user()->languagePreference?->native_locale ?? 'de',
            ));
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
