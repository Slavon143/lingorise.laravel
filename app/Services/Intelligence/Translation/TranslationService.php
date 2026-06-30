<?php

namespace App\Services\Intelligence\Translation;

use App\Enums\AiOperationType;
use App\Enums\AiUsageStatus;
use App\Enums\CostCalculationType;
use App\Models\Book;
use App\Services\Intelligence\Budget\AiBudgetGuard;
use App\Services\Intelligence\Cache\AiTextNormalizer;
use App\Services\Intelligence\Cache\TranslationCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Cost\AiCostCalculator;
use App\Services\Intelligence\Exceptions\AiBudgetExceededException;
use App\Services\Intelligence\Usage\AiUsageContext;
use App\Services\Intelligence\Usage\AiUsageRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TranslationService
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly TranslationCacheRepository $cache,
        private readonly AiProviderInterface $provider,
        private readonly AiUsageRecorder $usageRecorder,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiBudgetGuard $budget,
    ) {}

    /**
     * @return array{translation: string, pronunciation: string, explanation: string, meta: array}
     */
    public function translate(
        string $text,
        string $context,
        string $sourceLanguage,
        string $targetLanguage,
        string $model = 'gpt-5.4-mini',
        string $provider = 'openai',
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $normalizedText = $this->normalizer->normalizeForCache($text);
        $normalizedContext = $this->normalizer->normalizeForCache($context);

        $privacyScope = $book?->isPublic() ? 'public' : 'book';
        $scopeId = $book?->isPublic() ? null : $book?->id;

        $cacheKey = $this->cache->cacheKey(
            $normalizedText, $sourceLanguage, $targetLanguage,
            $model, $provider,
            privacyScope: $privacyScope, scopeId: $scopeId,
        );

        $cached = $this->cache->find($cacheKey);

        if ($cached) {
            $this->usageRecorder->log([
                'request_uuid' => Str::uuid()->toString(),
                'user_id' => $userId,
                'book_id' => $book?->id,
                'operation_type' => AiOperationType::Translation->value,
                'provider' => $provider,
                'model' => $model,
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'provider_called' => false,
                'request_characters' => mb_strlen($normalizedText),
                'response_characters' => mb_strlen($cached->translated_text),
                'cost_calculation_type' => CostCalculationType::CacheReference->value,
                'status' => AiUsageStatus::Success->value,
                'ip_hash' => $usageContext?->ipHash,
                'user_agent_hash' => $usageContext?->userAgentHash,
            ]);

            return [
                'translation' => $cached->translated_text,
                'pronunciation' => $cached->pronunciation ?? '',
                'explanation' => '',
                'meta' => [
                    'cache_hit' => true,
                    'provider_called' => false,
                    'cache_key' => $cacheKey,
                ],
            ];
        }

        $lockKey = 'ai:translation:' . $cacheKey;

        return Cache::lock($lockKey, 45)->block(15, function () use (
            $book, $cacheKey, $model, $normalizedContext, $normalizedText,
            $provider, $sourceLanguage, $targetLanguage, $text,
            $userId, $usageContext, $context,
        ): array {
            $cached = $this->cache->find($cacheKey);

            if ($cached) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Translation->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($normalizedText),
                    'response_characters' => mb_strlen($cached->translated_text),
                    'cost_calculation_type' => CostCalculationType::CacheReference->value,
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return [
                    'translation' => $cached->translated_text,
                    'pronunciation' => $cached->pronunciation ?? '',
                    'explanation' => '',
                    'meta' => [
                        'cache_hit' => true,
                        'provider_called' => false,
                        'cache_key' => $cacheKey,
                    ],
                ];
            }

            if (! $this->budget->isAllowed(AiOperationType::Translation)) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Translation->value,
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

                throw new AiBudgetExceededException('Translation budget exceeded.');
            }

            try {
                $started = microtime(true);

                $request = new TranslationRequest(
                    text: $text,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                    provider: $provider,
                    model: $model,
                    context: $context,
                );

                $result = $this->provider->translate($request);

                $this->cache->store(
                    $cacheKey, $normalizedText,
                    $sourceLanguage, $targetLanguage,
                    $result->text, $result->pronunciation ?? '',
                    $model, $provider,
                );

                $duration = (int) round((microtime(true) - $started) * 1000);

                $costResult = $this->costCalculator->calculateTextCost(
                    $provider, $model,
                    $result->inputTokens, $result->outputTokens, $result->cachedInputTokens,
                );

                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Translation->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($normalizedText),
                    'response_characters' => mb_strlen($result->text),
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'cached_input_tokens' => $result->cachedInputTokens,
                    'cost_calculation_type' => $costResult['calculationType'],
                    'estimated_cost_usd' => $costResult['cost'],
                    'duration_ms' => $duration,
                    'provider_duration_ms' => $result->providerDurationMs,
                    'pricing_version' => $costResult['pricingVersion'],
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return [
                    'translation' => $result->text,
                    'pronunciation' => $result->pronunciation ?? '',
                    'explanation' => '',
                    'meta' => [
                        'cache_hit' => false,
                        'provider_called' => true,
                        'cache_key' => $cacheKey,
                    ],
                ];
            } catch (Throwable $exception) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Translation->value,
                    'provider' => $provider,
                    'model' => $model,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($normalizedText),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::Failed->value,
                    'error_code' => $exception::class,
                    'safe_error_message' => $exception->getMessage(),
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw $exception;
            }
        });
    }
}
