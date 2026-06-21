<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Book::public()
            ->with('owner')
            ->whereNotNull('owner_id');

        if ($search = $request->query('search')) {
            $query->search($search);
        }

        if ($level = $request->query('level')) {
            $query->where('level', $level);
        }

        if ($language = $request->query('language')) {
            $query->where('language_locale', $language);
        }

        $books = $query->latest()->paginate(24);

        return view('library.public', [
            'books' => $books,
            'search' => $search,
            'selectedLevel' => $level,
            'selectedLanguage' => $language,
        ]);
    }

    public function addToMyLibrary(Request $request, Book $book): RedirectResponse
    {
        abort_unless($book->isPublic(), 404);

        $existing = $request->user()->books()
            ->where('original_book_id', $book->id)
            ->first();

        if ($existing) {
            return redirect()->route('library.index')
                ->with('status', 'This book is already in your library.');
        }

        $request->user()->books()->create([
            'title' => $book->title,
            'author' => $book->author,
            'category' => $book->category,
            'language_locale' => $book->language_locale,
            'level' => $book->level,
            'source_type' => $book->source_type,
            'cover_path' => $book->cover_path,
            'content' => $book->content,
            'total_words' => $book->total_words,
            'visibility' => 'private',
            'original_book_id' => $book->id,
            'processing_status' => 'ready',
        ]);

        return redirect()->route('library.index')
            ->with('status', 'Book added to your library.');
    }
}
