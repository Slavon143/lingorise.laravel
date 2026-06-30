<?php

namespace App\Console\Commands;

use App\Enums\AiOperationType;
use App\Models\User;
use Illuminate\Console\Command;

class AiQuotaSummaryCommand extends Command
{
    protected $signature = 'ai:quota-summary {--user= : Filter by user ID}';

    protected $description = 'Show AI quota usage summary for users.';

    public function handle(): int
    {
        $query = User::query();
        $userId = $this->option('user');

        if ($userId) {
            $query->where('id', (int) $userId);
        }

        $users = $query->limit(50)->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Plan', 'Translations (d/m)', 'Explanations (d/m)', 'TTS min (m)'];
        $rows = $users->map(function (User $user): array {
            $plan = $user->plan();

            return [
                $user->id,
                $user->name,
                $plan?->name ?? '?',
                '?/?',
                '?/?',
                '?',
            ];
        });

        $this->table($headers, $rows);
        $this->line('Run "php artisan tinker" for detailed quota queries.');

        return self::SUCCESS;
    }
}
