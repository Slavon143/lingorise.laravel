<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        return view('pricing.index');
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $request->user()->update(['plan' => 'pro']);

        return redirect()->route('pricing.index')
            ->with('status', 'Welcome to LingoRise Pro!');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->user()->update(['plan' => 'free']);

        return redirect()->route('pricing.index')
            ->with('status', 'You have been moved to the Free plan.');
    }
}
