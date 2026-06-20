<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('languagePreference');

        return view('dashboard', [
            'user' => $user,
            'preference' => $user->languagePreference,
        ]);
    }

    public function updateLanguages(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'native_locale' => ['required', 'string', 'max:10', 'different:learning_locale'],
            'learning_locale' => ['required', 'string', 'max:10', 'different:native_locale'],
        ]);

        $request->user()->languagePreference()->updateOrCreate([], $validated);

        return back()->with('status', 'Your language settings have been saved.');
    }
}
