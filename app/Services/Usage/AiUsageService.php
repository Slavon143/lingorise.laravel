<?php

namespace App\Services\Usage;

use App\Models\AiUsageLog;

class AiUsageService
{
    public function getDailyAiUsage(int $userId): int
    {
        return AiUsageLog::where('user_id', $userId)
            ->whereDate('created_at', now())
            ->sum('characters_count');
    }

    public function getMonthlyAiUsage(int $userId): int
    {
        return AiUsageLog::where('user_id', $userId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('characters_count');
    }

    public function getTtsCharactersThisMonth(int $userId): int
    {
        return AiUsageLog::where('user_id', $userId)
            ->where('type', 'tts')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('characters_count');
    }

    public function logUsage(
        int $userId,
        string $type,
        int $charactersCount,
        string $model = null,
        string $cacheKey = null,
        bool $cacheHit = false,
        string $status = 'success'
    ): void
    {
        AiUsageLog::create([
            'user_id' => $userId,
            'type' => $type,
            'cache_key' => $cacheKey,
            'characters_count' => $charactersCount,
            'cache_hit' => $cacheHit,
            'model' => $model,
            'duration_ms' => 0,
            'status' => $status,
            'error_code' => null,
        ]);
    }
}