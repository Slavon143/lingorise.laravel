<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\EpubTextExtractor;
use App\Services\Plans\ReaderEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->books();

        if ($search = $request->query('search')) {
            $query->search($search);
        }

        $books = $query->latest()->get();

        return view('library.index', compact('books', 'search'));
    }

    public function create(): View
    {
        return view('library.create');
    }

    public function metadata(Request $request, EpubTextExtractor $epubExtractor): JsonResponse
    {
        $validated = $request->validate([
            'book_file' => ['required', 'file', 'max:10240', 'extensions:txt,epub'],
        ]);

        $file = $validated['book_file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $metadata = [
            'title' => $this->titleFromFilename($file->getClientOriginalName()),
            'author' => null,
            'category' => 'Fiction',
            'language_locale' => 'en',
            'level' => 'A2',
            'visibility' => 'private',
        ];

        if ($extension === 'epub') {
            try {
                $metadata = array_merge($metadata, $epubExtractor->metadata($file->getRealPath()));
            } catch (RuntimeException) {
                // Filename defaults are still useful if EPUB metadata cannot be read.
            }
        }

        return response()->json(['metadata' => $metadata]);
    }

    public function store(StoreBookRequest $request, EpubTextExtractor $epubExtractor, ReaderEntitlementService $entitlements): RedirectResponse
    {
        if (! $entitlements->canImportBook($request->user())) {
            $limit = $entitlements->getLimit($request->user(), 'private_books_limit');

            return back()->withInput()->withErrors([
                'book_file' => $limit === null
                    ? 'Private book import is not available on your current plan.'
                    : "Your plan allows up to {$limit} private books. Upgrade to add more.",
            ]);
        }

        $content = trim((string) $request->input('content'));
        $sourceType = 'text';
        $coverPath = null;

        if ($request->hasFile('book_file')) {
            $file = $request->file('book_file');
            $sourceType = strtolower($file->getClientOriginalExtension());

            try {
                $content = $sourceType === 'epub'
                    ? $epubExtractor->extract($file->getRealPath())
                    : trim((string) file_get_contents($file->getRealPath()));

                if ($sourceType === 'epub' && ! $request->hasFile('cover_file')) {
                    $cover = $epubExtractor->extractCover($file->getRealPath());

                    if ($cover) {
                        $coverPath = 'book-covers/'.Str::uuid().'.'.$cover['extension'];
                        Storage::disk('public')->put($coverPath, $cover['contents']);
                    }
                }
            } catch (RuntimeException $exception) {
                return back()->withInput()->withErrors(['book_file' => $exception->getMessage()]);
            }
        }

        if ($content === '') {
            return back()->withInput()->withErrors(['content' => 'The book content is empty.']);
        }

        if ($request->hasFile('cover_file')) {
            $coverPath = $request->file('cover_file')->store('book-covers', 'public');
        }

        $request->user()->books()->create([
            ...$request->safe()->only([
                'title',
                'author',
                'category',
                'language_locale',
                'level',
                'visibility',
            ]),
            'source_type' => $sourceType,
            'cover_path' => $coverPath,
            'content' => $content,
            'total_words' => Str::wordCount(strip_tags($content)),
            'processing_status' => 'ready',
        ]);

        return redirect()->route('library.index')->with('status', 'Your book is ready to read.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        abort_unless($book->owner_id === $request->user()->id, 403);
        $book->delete();

        return back()->with('status', 'The book has been removed.');
    }

    public function toggleVisibility(Request $request, Book $book): RedirectResponse
    {
        abort_unless($book->owner_id === $request->user()->id, 403);

        $book->update([
            'visibility' => $book->visibility === 'public' ? 'private' : 'public',
        ]);

        $status = $book->visibility === 'public' ? 'public' : 'private';

        return back()->with('status', "\"{$book->title}\" is now {$status}.");
    }

    private function titleFromFilename(string $filename): string
    {
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = preg_replace('/[_-]+/u', ' ', $title);
        $title = preg_replace('/\s+/u', ' ', trim($title));

        return Str::headline($title ?: 'Untitled book');
    }
}
