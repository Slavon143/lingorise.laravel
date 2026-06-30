<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\AdminAuditLog;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(Request $request): View
    {
        $languages = Language::query()
            ->withCount('books')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->query('q'));
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.languages.index', ['languages' => $languages]);
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Language::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'native_name' => $validated['native_name'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'supports_translation' => $validated['supports_translation'] ?? false,
            'supports_tts' => $validated['supports_tts'] ?? false,
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('status', 'Language created successfully.');
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $validated = $request->validated();

        $language->forceFill([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'native_name' => $validated['native_name'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'supports_translation' => $validated['supports_translation'] ?? false,
            'supports_tts' => $validated['supports_tts'] ?? false,
        ])->save();

        return redirect()
            ->route('admin.languages.index')
            ->with('status', 'Language updated successfully.');
    }

    public function toggleActive(Request $request, Language $language): RedirectResponse
    {
        $language->forceFill([
            'is_active' => ! $language->is_active,
        ])->save();

        AdminAuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'language.' . ($language->is_active ? 'activated' : 'deactivated'),
            'entity_type' => Language::class,
            'entity_id' => $language->id,
            'old_values' => ['is_active' => ! $language->is_active],
            'new_values' => ['is_active' => $language->is_active],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Language updated successfully.');
    }

    public function destroy(Request $request, Language $language): RedirectResponse
    {
        if ($language->books()->count() > 0) {
            return back()->withErrors([
                'language' => 'Cannot delete language with existing books. Remove or reassign the books first.',
            ]);
        }

        $language->delete();

        return redirect()
            ->route('admin.languages.index')
            ->with('status', 'Language deleted successfully.');
    }
}
