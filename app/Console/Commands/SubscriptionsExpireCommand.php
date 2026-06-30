<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\UserSubscription;
use Illuminate\Console\Command;

class SubscriptionsExpireCommand extends Command
{
    protected $signature = 'subscriptions:expire {--dry-run : Preview without making changes}';

    protected $description = 'Expire subscriptions whose ends_at has passed.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expired = UserSubscription::whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No subscriptions to expire.');
            return self::SUCCESS;
        }

        $this->warn($dryRun ? 'DRY RUN — Would expire:' : 'Expiring:');
        $this->table(['ID', 'User ID', 'Plan', 'Ended at'], $expired->map(fn ($s) => [
            $s->id,
            $s->user_id,
            $s->plan?->name ?? '?',
            $s->ends_at?->toDateTimeString(),
        ]));

        if (! $dryRun) {
            $expired->each->update(['status' => SubscriptionStatus::Expired->value]);
            $this->info("Expired {$expired->count()} subscription(s).");
        }

        return self::SUCCESS;
    }
}
