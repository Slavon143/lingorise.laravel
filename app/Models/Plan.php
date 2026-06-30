<?php

namespace App\Models;

use App\Enums\PlanCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'is_active', 'is_default',
        'price_amount', 'price_currency', 'billing_interval', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'price_amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function isFree(): bool
    {
        return $this->code === PlanCode::Free->value;
    }

    public function isPremium(): bool
    {
        return $this->code === PlanCode::Premium->value;
    }

    public function isPro(): bool
    {
        return $this->code === PlanCode::Pro->value;
    }

    public function isAdmin(): bool
    {
        return $this->code === PlanCode::Admin->value;
    }

    public function aiLimits(): HasOne
    {
        return $this->hasOne(PlanAiLimit::class);
    }

    public function readerSettings(): HasOne
    {
        return $this->hasOne(PlanReaderSettings::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }
}
