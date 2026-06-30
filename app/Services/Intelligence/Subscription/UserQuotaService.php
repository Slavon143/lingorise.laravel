<?php

namespace App\Services\Intelligence\Subscription;

use App\Enums\AiOperationType;
use App\Models\AiUsageEvent;
use Illuminate\Support\Carbon;

class UserQuotaService
{
    public function countOperations(int $userId, AiOperationType $type, Carbon $since): int
    {
        return AiUsageEvent::where('user_id', $userId)
            ->where('operation_type', $type->value)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function countProviderCalls(int $userId, AiOperationType $type, Carbon $since): int
    {
        return AiUsageEvent::where('user_id', $userId)
            ->where('operation_type', $type->value)
            ->where('provider_called', true)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function ttsMinutesUsed(int $userId, Carbon $since): int
    {
        $ms = AiUsageEvent::where('user_id', $userId)
            ->where('operation_type', AiOperationType::Tts->value)
            ->where('provider_called', true)
            ->where('created_at', '>=', $since)
            ->sum('audio_duration_ms');

        return (int) round($ms / 60000);
    }

    public function dailyPeriod(?string $timezone = null): Carbon
    {
        return now($timezone)->startOfDay()->timezone('UTC');
    }

    public function monthlyPeriod(?string $timezone = null): Carbon
    {
        return now($timezone)->startOfMonth()->timezone('UTC');
    }
}
