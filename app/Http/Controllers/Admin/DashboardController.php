<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'newUsers' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'adminUsers' => User::where('is_admin', true)->count(),
            'booksCount' => Book::count(),
            'lastRegistration' => User::latest('created_at')->first(['created_at'])?->created_at,
            'latestUsers' => User::query()
                ->select(['id', 'name', 'email', 'is_admin', 'created_at'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
