<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\UserSubscription;
use Illuminate\Console\Command;

class SubscriptionsSummaryCommand extends Command
{
    protected $signature = 'subscriptions:summary';

    protected $description = 'Show subscription statistics.';

    public function handle(): int
    {
        $total = UserSubscription::count();
        $active = UserSubscription::where('status', 'active')->count();
        $expired = UserSubscription::where('status', 'expired')->count();
        $cancelled = UserSubscription::where('status', 'cancelled')->count();
        $trialing = UserSubscription::where('status', 'trialing')->count();

        $this->info('Subscription summary');
        $this->table(['Metric', 'Count'], [
            ['Total subscriptions', $total],
            ['Active', $active],
            ['Trialing', $trialing],
            ['Expired', $expired],
            ['Cancelled', $cancelled],
        ]);

        $byPlan = Plan::withCount('subscriptions')->get();
        $this->info('Per plan:');
        $this->table(['Plan', 'Subscriptions'], $byPlan->map(fn ($p) => [$p->name, $p->subscriptions_count]));

        return self::SUCCESS;
    }
}
