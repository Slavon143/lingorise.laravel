<?php

namespace App\Services\Intelligence\Usage;

use App\Enums\CostCalculationType;
use App\Models\AiUsageLog;
use App\Models\AiUsageEvent;
use Throwable;

class AiUsageRecorder
{
    public function log(array $data): void
    {
        try {
            $event = AiUsageEvent::create($data);
            $this->logToLegacy($data, $event->id);
        } catch (Throwable) {
            // Usage logging must never break the user experience.
        }
    }

    private function logToLegacy(array $data, int $eventId): void
    {
        try {
            AiUsageLog::create([
                'user_id' => $data['user_id'] ?? null,
                'type' => $data['operation_type'] ?? 'unknown',
                'cache_key' => $data['cache_key'] ?? null,
                'characters_count' => ($data['request_characters'] ?? 0) + ($data['response_characters'] ?? 0),
                'cache_hit' => $data['cache_hit'] ?? false,
                'model' => $data['model'] ?? null,
                'duration_ms' => $data['duration_ms'] ?? null,
                'status' => $data['status'] ?? 'unknown',
                'error_code' => $data['error_code'] ?? null,
            ]);
        } catch (Throwable) {
            // Legacy logging must never break the user experience.
        }
    }
}
