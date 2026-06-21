<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SpeakingController extends Controller
{
    public function index(Request $request): View
    {
        $entries = $request->user()->dictionaryEntries()
            ->with('book:id,title,language_locale')
            ->latest('updated_at')
            ->get();

        $currentIndex = max(0, $entries->search(fn ($entry) => $entry->id === $request->integer('entry')));
        $entry = $entries->get($currentIndex) ?? $entries->first();
        $nextEntry = $entries->isNotEmpty() ? $entries->get(($currentIndex + 1) % $entries->count()) : null;

        $languageNames = [
            'en' => 'English', 'de' => 'German', 'es' => 'Spanish',
            'fr' => 'French', 'sv' => 'Swedish',
        ];
        $locale = $entry?->book?->language_locale ?? 'en';
        $languageName = $languageNames[$locale] ?? 'English';

        return view('speaking.index', [
            'entry' => $entry,
            'nextEntry' => $nextEntry,
            'totalEntries' => $entries->count(),
            'position' => $entry ? $currentIndex + 1 : 0,
            'languageName' => $languageName,
        ]);
    }
}
