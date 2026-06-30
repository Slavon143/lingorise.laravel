<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'status',
        'starts_at', 'ends_at', 'cancelled_at',
        'source', 'external_provider', 'external_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active->value;
    }

    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::Expired->value;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled->value;
    }
}
