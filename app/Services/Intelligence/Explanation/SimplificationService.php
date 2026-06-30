<?php

namespace App\Services\Intelligence\Explanation;

use App\Enums\AiOperationType;
use App\Enums\AiUsageStatus;
use App\Enums\CostCalculationType;
use App\Models\Book;
use App\Services\Intelligence\Budget\AiBudgetGuard;
use App\Services\Intelligence\Cache\AiStructuredCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Cost\AiCostCalculator;
use App\Services\Intelligence\Exceptions\AiBudgetExceededException;
use App\Services\Intelligence\Exceptions\AiInvalidResponseException;
use App\Services\Intelligence\Usage\AiUsageContext;
use App\Services\Intelligence\Usage\AiUsageRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SimplificationService
{
    public function __construct(
        private readonly AiStructuredCacheRepository $cache,
        private readonly AiProviderInterface $provider,
        private readonly AiUsageRecorder $usageRecorder,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiBudgetGuard $budget,
    ) {}

    private const array ALLOWED_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1'];

    public function simplify(
        string $text,
        string $sourceLanguage,
        string $targetLevel,
        bool $preserveStyle = false,
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $privacyScope = $book?->isPublic() ? 'public' : 'book';
        $scopeId = $book?->isPublic() ? null : $book?->id;

        $cacheKey = $this->cache->cacheKey(
            operationType: 'simplification',
            sourceText: $text,
            sourceLanguage: $sourceLanguage,
            targetLanguage: $targetLevel,
            context: null,
            targetLevel: $targetLevel,
            model: 'gpt-4o-mini',
            privacyScope: $privacyScope,
            scopeId: $scopeId,
        );

        $cached = $this->cache->find($cacheKey);

        if ($cached) {
            $this->usageRecorder->log([
                'request_uuid' => Str::uuid()->toString(),
                'user_id' => $userId,
                'book_id' => $book?->id,
                'operation_type' => AiOperationType::Simplification->value,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'provider_called' => false,
                'request_characters' => mb_strlen($text),
                'response_characters' => mb_strlen(json_encode($cached->response_json)),
                'cost_calculation_type' => CostCalculationType::CacheReference->value,
                'status' => AiUsageStatus::Success->value,
                'ip_hash' => $usageContext?->ipHash,
                'user_agent_hash' => $usageContext?->userAgentHash,
            ]);

            return $this->buildResponse($cached->response_json, true, $cacheKey);
        }

        $lockKey = 'ai:simplification:' . $cacheKey;

        return Cache::lock($lockKey, 45)->block(15, function () use (
            $book, $cacheKey, $text,
            $sourceLanguage, $targetLevel, $preserveStyle,
            $userId, $usageContext,
        ): array {
            $cached = $this->cache->find($cacheKey);

            if ($cached) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Simplification->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($text),
                    'response_characters' => mb_strlen(json_encode($cached->response_json)),
                    'cost_calculation_type' => CostCalculationType::CacheReference->value,
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return $this->buildResponse($cached->response_json, true, $cacheKey);
            }

            if (! $this->budget->isAllowed(AiOperationType::Simplification)) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Simplification->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($text),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::BudgetBlocked->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw new AiBudgetExceededException('Simplification budget exceeded.');
            }

            try {
                $started = microtime(true);

                $request = new SimplificationData(
                    text: $text,
                    sourceLanguage: $sourceLanguage,
                    targetLevel: $targetLevel,
                    preserveStyle: $preserveStyle,
                );

                Log::debug('SimplificationService: calling provider', [
                    'text' => mb_substr($text, 0, 100),
                    'source_language' => $sourceLanguage,
                    'target_level' => $targetLevel,
                ]);

                $result = $this->provider->simplify($request);

                Log::debug('SimplificationService: provider returned', [
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'provider_duration_ms' => $result->providerDurationMs,
                ]);

                $responseJson = $this->validateResponse($result);

                $this->cache->store(
                    cacheKey: $cacheKey,
                    operationType: 'simplification',
                    sourceText: $text,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLevel,
                    responseJson: $responseJson,
                    targetLevel: $targetLevel,
                    model: 'gpt-4o-mini',
                    privacyScope: $book?->isPublic() ? 'public' : 'book',
                    scopeId: $book?->isPublic() ? null : $book?->id,
                );

                $duration = (int) round((microtime(true) - $started) * 1000);

                $costResult = $this->costCalculator->calculateTextCost(
                    'openai', 'gpt-4o-mini',
                    $result->inputTokens, $result->outputTokens,
                );

                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Simplification->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($text),
                    'response_characters' => mb_strlen(json_encode($responseJson)),
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'cost_calculation_type' => $costResult['calculationType'],
                    'estimated_cost_usd' => $costResult['cost'],
                    'duration_ms' => $duration,
                    'provider_duration_ms' => $result->providerDurationMs,
                    'pricing_version' => $costResult['pricingVersion'],
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return $this->buildResponse($responseJson, false, $cacheKey);
            } catch (Throwable $exception) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::Simplification->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($text),
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

    private function validateResponse(SimplificationResult $result): array
    {
        $original = trim($result->original);
        $simplified = trim($result->simplified);

        if ($original === '' || $simplified === '') {
            throw new AiInvalidResponseException('Simplification returned empty required fields.');
        }

        if (!in_array($result->targetLevel, self::ALLOWED_LEVELS, true)) {
            throw new AiInvalidResponseException('Invalid target CEFR level in simplification response.');
        }

        return [
            'original' => $original,
            'simplified' => $simplified,
            'target_level' => $result->targetLevel,
            'replacements' => $result->replacements,
            'changes_explanation' => $result->changesExplanation ? trim($result->changesExplanation) : null,
            'meaning_adapted' => $result->meaningAdapted,
            'meaning_adapted_warning' => $result->meaningAdaptedWarning ? trim($result->meaningAdaptedWarning) : null,
        ];
    }

    private function buildResponse(array $data, bool $cacheHit, string $cacheKey): array
    {
        return [
            'data' => $data,
            'meta' => [
                'cache_hit' => $cacheHit,
                'cache_key' => $cacheKey,
            ],
        ];
    }
}
