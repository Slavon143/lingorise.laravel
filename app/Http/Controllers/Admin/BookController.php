<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\AdminAuditLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'title', 'author', 'language_locale', 'level', 'difficulty', 'category', 'access_type', 'processing_status', 'total_words', 'is_featured', 'published_at', 'created_at', 'updated_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $books = Book::query()
            ->select(['id', 'title', 'slug', 'author', 'language_locale', 'level', 'difficulty', 'category', 'access_type', 'processing_status', 'total_words', 'is_featured', 'owner_id', 'author_id', 'category_id', 'language_id', 'user_id', 'cover_path', 'published_at', 'created_at', 'updated_at'])
            ->with(['owner:id,name', 'authorRelation:id,name', 'categoryRelation:id,name', 'languageRelation:id,name,code', 'bookOwner:id,name'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->query('q'));
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%");
                });
            })
            ->when($request->query('access_type'), function ($query, string $type): void {
                $query->where('access_type', $type);
            })
            ->when($request->query('status'), function ($query, string $status): void {
                $query->where('processing_status', $status);
            })
            ->when($request->query('language_id'), function ($query, string $id): void {
                $query->where('language_id', $id);
            })
            ->when($request->query('category_id'), function ($query, string $id): void {
                $query->where('category_id', $id);
            })
            ->when($request->query('owner_id'), function ($query, string $id): void {
                $query->where('owner_id', $id)->orWhere('user_id', $id);
            })
            ->when($request->query('featured') === 'yes', fn ($query) => $query->where('is_featured', true))
            ->when($request->query('featured') === 'no', fn ($query) => $query->where('is_featured', false))
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', fn ($query) => $query->orderBy('id', $direction))
            ->paginate(15)
            ->withQueryString();

        return view('admin.books.index', [
            'books' => $books,
            'sort' => $sort,
            'direction' => $direction,
            'languages' => Language::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(['id', 'name']),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Book $book): View
    {
        $book->load(['owner:id,name', 'authorRelation:id,name', 'categoryRelation:id,name', 'languageRelation:id,name,code', 'bookOwner:id,name', 'createdBy:id,name']);
        $book->loadCount('readingProgress', 'dictionaryEntries');

        return view('admin.books.show', ['managedBook' => $book]);
    }

    public function create(): View
    {
        return view('admin.books.create', [
            'languages' => Language::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $coverPath = $this->handleCoverUpload($request, null);
        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        $book = new Book;
        $languageLocale = null;
        if ($validated['language_id'] ?? null) {
            $languageLocale = Language::find($validated['language_id'])?->code;
        }

        $book->forceFill([
            'title' => $validated['title'],
            'slug' => $slug,
            'subtitle' => $validated['subtitle'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'language_id' => $validated['language_id'] ?? null,
            'language_locale' => $languageLocale ?? 'en',
            'difficulty' => $validated['difficulty'] ?? null,
            'access_type' => $validated['access_type'] ?? 'public',
            'processing_status' => $validated['status'] ?? 'draft',
            'is_featured' => $validated['is_featured'] ?? false,
            'cover_path' => $coverPath,
            'published_at' => $validated['published_at'] ?? null,
            'owner_id' => $validated['owner_id'] ?? $request->user()->id,
            'user_id' => $validated['owner_id'] ?? null,
            'created_by' => $request->user()->id,
            'visibility' => ($validated['access_type'] ?? 'public') === 'public' ? 'public' : 'private',
            'content' => '',
            'source_type' => 'admin',
            'total_words' => 0,
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.created',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => [],
            'new_values' => $book->only(['title', 'slug', 'access_type', 'processing_status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.books.show', $book)
            ->with('status', 'Book created successfully.');
    }

    public function edit(Book $book): View
    {
        return view('admin.books.edit', [
            'managedBook' => $book,
            'languages' => Language::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $oldValues = $book->only(['title', 'slug', 'subtitle', 'author_id', 'category_id', 'language_id', 'difficulty', 'access_type', 'processing_status', 'is_featured', 'published_at', 'owner_id']);

        $coverPath = $book->cover_path;

        if ($request->hasFile('cover')) {
            $coverPath = $this->handleCoverUpload($request, $book->cover_path);
        }

        $languageLocale = $book->language_locale;
        if ($validated['language_id'] ?? null) {
            $languageLocale = Language::find($validated['language_id'])?->code;
        } elseif (empty($book->language_locale)) {
            $languageLocale = 'en';
        }

        $book->forceFill([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'subtitle' => $validated['subtitle'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'language_id' => $validated['language_id'] ?? null,
            'language_locale' => $languageLocale,
            'difficulty' => $validated['difficulty'] ?? null,
            'access_type' => $validated['access_type'] ?? 'public',
            'processing_status' => $validated['status'] ?? 'draft',
            'is_featured' => $validated['is_featured'] ?? false,
            'cover_path' => $coverPath,
            'published_at' => $validated['published_at'] ?? null,
            'owner_id' => $validated['owner_id'] ?? $book->owner_id,
            'user_id' => $validated['owner_id'] ?? $book->user_id,
            'visibility' => ($validated['access_type'] ?? 'public') === 'public' ? 'public' : 'private',
        ])->save();

        $newValues = $book->only(['title', 'slug', 'subtitle', 'author_id', 'category_id', 'language_id', 'difficulty', 'access_type', 'processing_status', 'is_featured', 'published_at', 'owner_id']);

        if ($oldValues !== $newValues) {
            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'book.updated',
                'entity_type' => Book::class,
                'entity_id' => $book->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.books.show', $book)
            ->with('status', 'Book updated successfully.');
    }

    public function publish(Request $request, Book $book): RedirectResponse
    {
        $book->forceFill([
            'processing_status' => 'published',
            'published_at' => $book->published_at ?? now(),
            'visibility' => $book->access_type === 'public' ? 'public' : 'private',
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.published',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => ['processing_status' => 'ready'],
            'new_values' => ['processing_status' => 'published', 'published_at' => $book->published_at],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Book published successfully.');
    }

    public function unpublish(Request $request, Book $book): RedirectResponse
    {
        $book->forceFill([
            'processing_status' => 'ready',
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.unpublished',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => ['processing_status' => 'published'],
            'new_values' => ['processing_status' => 'ready'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Book unpublished successfully.');
    }

    public function archive(Request $request, Book $book): RedirectResponse
    {
        $book->forceFill([
            'processing_status' => 'archived',
            'archived_at' => now(),
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.archived',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => ['processing_status' => $book->getOriginal('processing_status')],
            'new_values' => ['processing_status' => 'archived', 'archived_at' => $book->archived_at],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Book archived successfully.');
    }

    public function restore(Request $request, Book $book): RedirectResponse
    {
        $book->forceFill([
            'processing_status' => 'draft',
            'archived_at' => null,
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.restored',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => ['processing_status' => 'archived'],
            'new_values' => ['processing_status' => 'draft', 'archived_at' => null],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Book restored successfully.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        $book->delete();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'book.deleted',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
            'old_values' => $book->only(['title', 'slug', 'processing_status']),
            'new_values' => [],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.books.index')
            ->with('status', 'Book deleted successfully.');
    }

    private function handleCoverUpload(Request $request, ?string $currentPath): ?string
    {
        if (! $request->hasFile('cover')) {
            return $currentPath;
        }

        $file = $request->file('cover');
        $path = $file->store('book-covers', 'public');

        if ($currentPath && $currentPath !== $path && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        return $path;
    }
}
