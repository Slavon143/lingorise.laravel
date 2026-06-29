<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use Throwable;

class AiUsageLogger
{
    public function log(
        ?int $userId,
        string $type,
        ?string $cacheKey,
        int $charactersCount,
        bool $cacheHit,
        ?string $model,
        ?int $durationMs,
        string $status,
        ?string $errorCode = null,
    ): void {
        try {
            AiUsageLog::create([
                'user_id' => $userId,
                'type' => $type,
                'cache_key' => $cacheKey,
                'characters_count' => $charactersCount,
                'cache_hit' => $cacheHit,
                'model' => $model,
                'duration_ms' => $durationMs,
                'status' => $status,
                'error_code' => $errorCode,
            ]);
        } catch (Throwable) {
            // Usage logging must never break the reader experience.
        }
    }
}
