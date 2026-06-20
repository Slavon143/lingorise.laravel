<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReaderController extends Controller
{
    private const WORDS_PER_PAGE = 350;

    public function show(Request $request, Book $book): View
    {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $words = preg_split('/\s+/u', trim($book->content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $totalPages = max(1, (int) ceil(count($words) / self::WORDS_PER_PAGE));
        $page = min(max((int) $request->integer('page', 1), 1), $totalPages);
        $pageWords = array_slice($words, ($page - 1) * self::WORDS_PER_PAGE, self::WORDS_PER_PAGE);
        $pageParagraphs = array_chunk($pageWords, 85);
        $wordsRead = min($page * self::WORDS_PER_PAGE, count($words));

        $request->user()->readingProgress()->updateOrCreate(
            ['book_id' => $book->id],
            [
                'current_page' => $page,
                'words_read' => $wordsRead,
                'last_read_at' => now(),
                'completed_at' => $page === $totalPages ? now() : null,
            ],
        );

        return view('reader.show', [
            'book' => $book,
            'page' => $page,
            'pageParagraphs' => $pageParagraphs,
            'totalPages' => $totalPages,
            'percentage' => (int) round(($page / $totalPages) * 100),
            'nativeLanguage' => $request->user()->languagePreference?->native_locale ?? 'de',
            'readingTime' => max(1, (int) ceil(count($pageWords) / 200)),
            'pageTitle' => Str::limit($book->title, 55),
        ]);
    }
}
