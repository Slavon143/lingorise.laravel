<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAuthorRequest;
use App\Http\Requests\Admin\UpdateAuthorRequest;
use App\Models\AdminAuditLog;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $authors = Author::query()
            ->withCount('books')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->query('q'));
                $query->where('name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.authors.index', ['authors' => $authors]);
    }

    public function create(): View
    {
        return view('admin.authors.form', ['author' => null]);
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $author = Author::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'bio' => $validated['bio'] ?? null,
            'country' => $validated['country'] ?? null,
            'birth_year' => $validated['birth_year'] ?? null,
            'death_year' => $validated['death_year'] ?? null,
        ]);

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'author.created',
            'entity_type' => Author::class,
            'entity_id' => $author->id,
            'old_values' => [],
            'new_values' => $author->only(['name', 'slug']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.authors.index')
            ->with('status', 'Author created successfully.');
    }

    public function edit(Author $author): View
    {
        return view('admin.authors.form', ['author' => $author]);
    }

    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $oldValues = $author->only(['name', 'slug', 'bio', 'country', 'birth_year', 'death_year']);
        $validated = $request->validated();

        $author->forceFill([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $author->slug,
            'bio' => $validated['bio'] ?? null,
            'country' => $validated['country'] ?? null,
            'birth_year' => $validated['birth_year'] ?? null,
            'death_year' => $validated['death_year'] ?? null,
        ])->save();

        $newValues = $author->only(['name', 'slug', 'bio', 'country', 'birth_year', 'death_year']);

        if ($oldValues !== $newValues) {
            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'author.updated',
                'entity_type' => Author::class,
                'entity_id' => $author->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.authors.index')
            ->with('status', 'Author updated successfully.');
    }

    public function destroy(Request $request, Author $author): RedirectResponse
    {
        if ($author->books()->count() > 0) {
            return back()->withErrors([
                'author' => 'Cannot delete author with existing books. Remove or reassign the books first.',
            ]);
        }

        $author->delete();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'author.deleted',
            'entity_type' => Author::class,
            'entity_id' => $author->id,
            'old_values' => $author->only(['name', 'slug']),
            'new_values' => [],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.authors.index')
            ->with('status', 'Author deleted successfully.');
    }
}
