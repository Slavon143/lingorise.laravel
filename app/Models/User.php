<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\SubscriptionStatus;
use App\Services\Intelligence\Subscription\SubscriptionResolver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'email_verified_at', 'daily_goal_minutes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isPro(): bool
    {
        $plan = $this->plan();

        return $plan->isPremium() || $plan->isPro() || $plan->isAdmin();
    }

    public function plan(): Plan
    {
        return app(SubscriptionResolver::class)->resolvePlan($this);
    }

    public function subscription(): HasMany
    {
        return $this->hasMany(UserSubscription::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latestOfMany();
    }

    public function languagePreference(): HasOne
    {
        return $this->hasOne(LanguagePreference::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'owner_id');
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function dictionaryEntries(): HasMany
    {
        return $this->hasMany(DictionaryEntry::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'daily_goal_minutes' => 'integer',
            'password' => 'hashed',
        ];
    }
}
