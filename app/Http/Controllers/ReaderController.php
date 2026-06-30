<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Plans\ReaderEntitlementService;
use App\Services\ReaderTextFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function show(Request $request, Book $book, ReaderTextFormatter $formatter, ReaderEntitlementService $entitlements): View
    {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $pages = $formatter->pages($book->content);
        $totalPages = count($pages);
        $page = min(max((int) $request->integer('page', 1), 1), $totalPages);
        $pageBlocks = $pages[$page - 1];
        $pageWordCount = array_sum(array_map(
            fn (array $block): int => count(preg_split('/\s+/u', trim($block['text']), -1, PREG_SPLIT_NO_EMPTY) ?: []),
            $pageBlocks,
        ));
        $totalWords = $book->total_words ?? array_sum(array_map(
            fn (array $blocks): int => array_sum(array_map(
                fn (array $block): int => count(preg_split('/\s+/u', trim($block['text']), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                $blocks,
            )),
            $pages,
        ));
        $wordsRead = array_sum(array_map(
            fn (array $blocks): int => array_sum(array_map(
                fn (array $block): int => count(preg_split('/\s+/u', trim($block['text']), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                $blocks,
            )),
            array_slice($pages, 0, $page),
        ));
        $wordsRemaining = max(0, $totalWords - $wordsRead);
        $readingTimeRemaining = max(1, (int) ceil($wordsRemaining / 200));
        $readingTimeCurrent = max(1, (int) ceil($pageWordCount / 200));

        $previousProgress = $request->user()->readingProgress()
            ->where('book_id', $book->id)
            ->first();
        $previousWords = $previousProgress ? $previousProgress->words_read : 0;
        $newWordsThisSession = $wordsRead - $previousWords;

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
            'pageBlocks' => $pageBlocks,
            'totalPages' => $totalPages,
            'percentage' => (int) round(($page / $totalPages) * 100),
            'nativeLanguage' => $request->user()->languagePreference?->native_locale ?? 'de',
            'readingTime' => $readingTimeRemaining,
            'minutesThisSession' => max(0, (int) round($newWordsThisSession / 200)),
            'pageTitle' => Str::limit($book->title, 55),
            'focusPhrase' => trim((string) $request->query('focus')),
            'readerCapabilities' => $entitlements->getUserCapabilities($request->user()),
            'savedEntries' => $request->user()->dictionaryEntries()
                ->where('book_id', $book->id)
                ->latest('updated_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
