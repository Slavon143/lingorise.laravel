<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'Application name' => config('app.name'),
                'Environment' => app()->environment(),
                'Debug mode' => config('app.debug') ? 'enabled' : 'disabled',
                'Laravel version' => Application::VERSION,
                'PHP version' => PHP_VERSION,
                'Cache driver' => config('cache.default'),
                'Queue driver' => config('queue.default'),
                'Filesystem disk' => config('filesystems.default'),
                'Mail driver' => config('mail.default'),
                'OpenAI configured' => config('services.openai.key') ? 'yes' : 'no',
                'Stripe configured' => config('services.stripe.secret') ? 'yes' : 'no',
                'Mail configured' => config('mail.mailers.'.config('mail.default').'.host') ? 'yes' : 'no',
            ],
        ]);
    }
}
