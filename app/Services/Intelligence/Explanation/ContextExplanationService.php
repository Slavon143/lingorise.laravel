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
use Illuminate\Support\Str;
use Throwable;

class ContextExplanationService
{
    public function __construct(
        private readonly AiStructuredCacheRepository $cache,
        private readonly AiProviderInterface $provider,
        private readonly AiUsageRecorder $usageRecorder,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiBudgetGuard $budget,
    ) {}

    public function explain(
        string $selectedText,
        string $context,
        string $sourceLanguage,
        string $targetLanguage,
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $privacyScope = $book?->isPublic() ? 'public' : 'book';
        $scopeId = $book?->isPublic() ? null : $book?->id;

        $cacheKey = $this->cache->cacheKey(
            operationType: 'context_explanation',
            sourceText: $selectedText,
            sourceLanguage: $sourceLanguage,
            targetLanguage: $targetLanguage,
            context: $context,
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
                'operation_type' => AiOperationType::ContextExplanation->value,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'provider_called' => false,
                'request_characters' => mb_strlen($selectedText) + mb_strlen($context),
                'response_characters' => mb_strlen(json_encode($cached->response_json)),
                'cost_calculation_type' => CostCalculationType::CacheReference->value,
                'status' => AiUsageStatus::Success->value,
                'ip_hash' => $usageContext?->ipHash,
                'user_agent_hash' => $usageContext?->userAgentHash,
            ]);

            return $this->buildResponse($cached->response_json, true, $cacheKey);
        }

        $lockKey = 'ai:context_explanation:' . $cacheKey;

        return Cache::lock($lockKey, 45)->block(15, function () use (
            $book, $cacheKey, $context, $selectedText,
            $sourceLanguage, $targetLanguage, $userId, $usageContext,
        ): array {
            $cached = $this->cache->find($cacheKey);

            if ($cached) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::ContextExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($selectedText) + mb_strlen($context),
                    'response_characters' => mb_strlen(json_encode($cached->response_json)),
                    'cost_calculation_type' => CostCalculationType::CacheReference->value,
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return $this->buildResponse($cached->response_json, true, $cacheKey);
            }

            if (! $this->budget->isAllowed(AiOperationType::ContextExplanation)) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::ContextExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($selectedText) + mb_strlen($context),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::BudgetBlocked->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw new AiBudgetExceededException('Context explanation budget exceeded.');
            }

            try {
                $started = microtime(true);

                $request = new ContextExplanationData(
                    selectedText: $selectedText,
                    context: $context,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                );

                $result = $this->provider->explainInContext($request);

                $responseJson = $this->validateResponse($result);

                $this->cache->store(
                    cacheKey: $cacheKey,
                    operationType: 'context_explanation',
                    sourceText: $selectedText,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                    responseJson: $responseJson,
                    context: $context,
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
                    'operation_type' => AiOperationType::ContextExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($selectedText) + mb_strlen($context),
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
                    'operation_type' => AiOperationType::ContextExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($selectedText) + mb_strlen($context),
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

    private function validateResponse(ContextExplanationResult $result): array
    {
        $meaning = trim($result->meaningInContext);
        $translation = trim($result->translation);
        $explanation = trim($result->simpleExplanation);

        if ($meaning === '' || $translation === '' || $explanation === '') {
            throw new AiInvalidResponseException('Context explanation returned empty required fields.');
        }

        $allowedLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', null];
        if ($result->cefrLevel !== null && !in_array($result->cefrLevel, $allowedLevels, true)) {
            throw new AiInvalidResponseException('Invalid CEFR level in context explanation.');
        }

        $maxLength = 2000;
        if (mb_strlen($meaning) > $maxLength || mb_strlen($translation) > $maxLength || mb_strlen($explanation) > $maxLength) {
            throw new AiInvalidResponseException('Context explanation field exceeds maximum length.');
        }

        return [
            'meaning_in_context' => $meaning,
            'base_form' => $result->baseForm ? trim($result->baseForm) : null,
            'part_of_speech' => $result->partOfSpeech ? trim($result->partOfSpeech) : null,
            'translation' => $translation,
            'simple_explanation' => $explanation,
            'example' => $result->example ? trim($result->example) : null,
            'cefr_level' => $result->cefrLevel,
            'grammar_form' => $result->grammarForm ? trim($result->grammarForm) : null,
            'fixed_expression' => $result->fixedExpression ? trim($result->fixedExpression) : null,
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
