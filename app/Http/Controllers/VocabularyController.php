<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VocabularyController extends Controller
{
    public function store(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $validated = $request->validate([
            'original_text' => ['required', 'string', 'max:255'],
            'translated_text' => ['required', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = $request->user()->dictionaryEntries()->updateOrCreate(
            [
                'book_id' => $book->id,
                'original_text' => $validated['original_text'],
            ],
            [
                'translated_text' => $validated['translated_text'],
                'context' => $validated['context'] ?? null,
                'status' => 'new',
            ],
        );

        return response()->json([
            'saved' => true,
            'id' => $entry->id,
        ]);
    }
}
