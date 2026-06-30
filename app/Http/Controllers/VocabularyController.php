<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DictionaryEntry;
use App\Services\Intelligence\Subscription\EffectiveAiLimitsResolver;
use App\Services\ReaderTextFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VocabularyController extends Controller
{
    public function __construct(
        private readonly EffectiveAiLimitsResolver $limitsResolver,
    ) {}

    public function index(Request $request, ReaderTextFormatter $formatter): View
    {
        $search = trim((string) $request->query('q'));
        $bookId = $request->integer('book');

        $entries = $request->user()->dictionaryEntries()
            ->with('book:id,title,content')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('original_text', 'like', "%{$search}%")
                    ->orWhere('translated_text', 'like', "%{$search}%");
            }))
            ->when($bookId > 0, fn ($query) => $query->where('book_id', $bookId))
            ->latest('updated_at')
            ->paginate(24)
            ->withQueryString();

        $entries->getCollection()->each(function (DictionaryEntry $entry) use ($formatter): void {
            $entry->setAttribute(
                'reader_page',
                $entry->book ? $formatter->pageContaining($entry->book->content, $entry->original_text) : 1,
            );
        });

        return view('vocabulary.index', [
            'entries' => $entries,
            'books' => $request->user()->books()
                ->whereHas('dictionaryEntries', fn ($query) => $query->where('user_id', $request->user()->id))
                ->orderBy('title')
                ->get(['id', 'title']),
            'search' => $search,
            'selectedBook' => $bookId,
        ]);
    }

    public function store(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->owner_id === $request->user()->id || $book->isPublic(), 403);

        $limits = $this->limitsResolver->resolve($request->user());

        $entryCount = $request->user()->dictionaryEntries()->count();
        $vocabLimit = $limits->privateBooksLimit();

        if ($vocabLimit !== null && $entryCount >= $vocabLimit) {
            return response()->json([
                'saved' => false,
                'error' => "You've reached the limit of {$vocabLimit} saved words. Upgrade to Pro for unlimited vocabulary.",
                'upgrade_url' => route('pricing.index'),
            ], 403);
        }

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
            'entry' => [
                'original_text' => $entry->original_text,
                'translated_text' => $entry->translated_text,
            ],
        ]);
    }

    public function destroy(Request $request, DictionaryEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->delete();

        return back()->with('status', 'Vocabulary item removed.');
    }
}
