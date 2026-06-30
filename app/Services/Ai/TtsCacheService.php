<?php

namespace App\Services\Ai;

use App\Services\Intelligence\Cache\TtsCacheRepository;
use App\Services\Intelligence\Tts\TtsService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TtsCacheService
{
    public function __construct(
        private readonly TtsService $ttsService,
    ) {}

    /**
     * @return array{body: string, cache_key: string, cache_hit: bool, file_path: string}
     */
    public function audio(
        string $text,
        string $language,
        int $userId,
        string $voice,
        string $model,
        string $format = 'mp3',
        string $speed = '1',
    ): array {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('Natural voice is not configured.');
        }

        $usageContext = new AiUsageContext(userId: $userId);

        try {
            $result = $this->ttsService->synthesize(
                text: $text,
                language: $language,
                voice: $voice,
                speed: $speed,
                model: $model,
                format: $format,
                userId: $userId,
                usageContext: $usageContext,
            );

            return $result;
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
