<?php

namespace App\Services\Intelligence\Tts;

use App\Enums\AiOperationType;
use App\Enums\AiUsageStatus;
use App\Enums\CostCalculationType;
use App\Enums\TtsCacheStatus;
use App\Models\Book;
use App\Services\Intelligence\Budget\AiBudgetGuard;
use App\Services\Intelligence\Cache\AiTextNormalizer;
use App\Services\Intelligence\Cache\TtsCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Cost\AiCostCalculator;
use App\Services\Intelligence\Exceptions\AiBudgetExceededException;
use App\Services\Intelligence\Usage\AiUsageContext;
use App\Services\Intelligence\Usage\AiUsageRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TtsService
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly TtsCacheRepository $cache,
        private readonly TtsFileManager $fileManager,
        private readonly AiProviderInterface $provider,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiUsageRecorder $usageRecorder,
        private readonly AiBudgetGuard $budget,
    ) {}

    /**
     * @return array{body: string, cache_key: string, cache_hit: bool, file_path: string, meta: array}
     */
    public function synthesize(
        string $text,
        string $language,
        string $voice = 'marin',
        string $speed = '1',
        string $model = 'gpt-4o-mini-tts',
        string $provider = 'openai',
        string $format = 'mp3',
        ?int $sampleRate = null,
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $normalizedText = $this->normalizer->normalizeForTts($text);
        $cacheKey = $this->cache->cacheKey(
            $normalizedText, $language,
            $provider, $model, $voice, $speed, $format, $sampleRate,
        );

        $cached = $this->cache->findUsable($cacheKey);

        if ($cached) {
            $body = Storage::disk('local')->get($cached->file_path);

            $this->usageRecorder->log([
                'request_uuid' => Str::uuid()->toString(),
                'user_id' => $userId,
                'book_id' => $book?->id,
                'operation_type' => AiOperationType::Tts->value,
                'provider' => $provider,
                'model' => $model,
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'provider_called' => false,
                'request_characters' => mb_strlen($normalizedText),
                'audio_duration_ms' => $cached->duration_ms,
                'audio_size_bytes' => $cached->file_size,
                'cost_calculation_type' => CostCalculationType::CacheReference->value,
                'status' => AiUsageStatus::Success->value,
                'ip_hash' => $usageContext?->ipHash,
                'user_agent_hash' => $usageContext?->userAgentHash,
            ]);

            return [
                'body' => $body,
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'file_path' => $cached->file_path,
                'meta' => ['cache_hit' => true, 'provider_called' => false, 'cache_key' => $cacheKey],
            ];
        }

        $lockKey = 'ai:tts:' . $cacheKey;

        return Cache::lock($lockKey, 45)->block(15, function () use (
            $book, $cacheKey, $format, $language, $model, $normalizedText,
            $provider, $sampleRate, $speed, $text, $userId, $usageContext, $voice,
        ): array {
            $cached = $this->cache->findUsable($cacheKey);

            if ($cached) {
                $body = Storage::disk('local')->get($cached->file_path);

                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Tts->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($normalizedText),
                    'audio_duration_ms' => $cached->duration_ms,
                    'audio_size_bytes' => $cached->file_size,
                    'cost_calculation_type' => CostCalculationType::CacheReference->value,
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return [
                    'body' => $body,
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'file_path' => $cached->file_path,
                    'meta' => ['cache_hit' => true, 'provider_called' => false, 'cache_key' => $cacheKey],
                ];
            }

            if (! $this->budget->isAllowed(AiOperationType::Tts)) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Tts->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($normalizedText),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::BudgetBlocked->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw new AiBudgetExceededException('TTS budget exceeded.');
            }

            $this->cache->markGenerating($cacheKey, $normalizedText, [
                'language' => $language,
                'voice' => $voice,
                'speed' => (string) $speed,
                'model' => $model,
                'format' => $format,
                'source_text_hash' => sha1($normalizedText),
            ]);

            try {
                $started = microtime(true);

                $request = new TtsRequest(
                    text: $text,
                    language: $language,
                    voice: $voice,
                    speed: (string) $speed,
                    provider: $provider,
                    model: $model,
                    format: $format,
                    sampleRate: $sampleRate,
                );

                $result = $this->provider->synthesize($request);

                $filePath = $this->fileManager->storeAudio(
                    file_get_contents($result->temporaryFilePath),
                    $cacheKey,
                    $format,
                );

                $fileSize = $result->fileSizeBytes;
                $duration = (int) round((microtime(true) - $started) * 1000);

                $this->cache->markReady($cacheKey, $filePath, $fileSize, $result->durationMs);

                $costResult = $this->costCalculator->estimateFromDuration(
                    $provider, $model, $result->durationMs ?? $duration,
                );

                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Tts->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($normalizedText),
                    'audio_duration_ms' => $result->durationMs,
                    'audio_size_bytes' => $fileSize,
                    'cost_calculation_type' => $costResult['calculationType'],
                    'estimated_cost_usd' => $costResult['cost'],
                    'duration_ms' => $duration,
                    'provider_duration_ms' => $result->providerDurationMs,
                    'pricing_version' => $costResult['pricingVersion'],
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                $body = file_get_contents($result->temporaryFilePath);
                @unlink($result->temporaryFilePath);

                return [
                    'body' => $body,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'file_path' => $filePath,
                    'meta' => ['cache_hit' => false, 'provider_called' => true, 'cache_key' => $cacheKey],
                ];
            } catch (Throwable $exception) {
                $this->cache->markFailed(
                    $cacheKey,
                    $exception::class,
                    mb_substr($exception->getMessage(), 0, 500),
                );

                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Tts->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($normalizedText),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::Failed->value,
                    'error_code' => $exception::class,
                    'safe_error_message' => mb_substr($exception->getMessage(), 0, 500),
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw $exception;
            }
        });
    }
}
