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
        $cacheKey = $this->cache->cacheKey(
            operationType: 'context_explanation',
            sourceText: $selectedText,
            sourceLanguage: $sourceLanguage,
            targetLanguage: $targetLanguage,
            context: $context,
            model: 'gpt-4o-mini',
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

                Log::debug('ContextExplanationService: calling provider', [
                    'selected_text' => mb_substr($selectedText, 0, 100),
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                ]);

                $result = $this->provider->explainInContext($request);

                Log::debug('ContextExplanationService: provider returned', [
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'provider_duration_ms' => $result->providerDurationMs,
                ]);

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
        $expression = trim($result->expression);
        $meaning = trim($result->meaningInContext);
        $why = trim($result->whyThisMeaning);

        if ($expression === '' || $meaning === '' || $why === '') {
            throw new AiInvalidResponseException('Context explanation returned empty required fields.');
        }

        $allowedLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        if ($result->cefrLevel !== null && !in_array($result->cefrLevel, $allowedLevels, true)) {
            throw new AiInvalidResponseException('Invalid CEFR level in context explanation.');
        }

        $maxLength = 2000;
        if (mb_strlen($expression) > $maxLength || mb_strlen($meaning) > $maxLength || mb_strlen($why) > $maxLength) {
            throw new AiInvalidResponseException('Context explanation field exceeds maximum length.');
        }

        return [
            'expression' => $expression,
            'meaning_in_context' => $meaning,
            'why_this_meaning' => $why,
            'role_in_sentence' => $result->roleInSentence,
            'base_form' => $result->baseForm,
            'part_of_speech' => $result->partOfSpeech,
            'fixed_expression' => $result->fixedExpression,
            'literal_translation_warning' => $result->literalTranslationWarning,
            'register' => $result->register,
            'connotation' => $result->connotation,
            'synonyms' => array_values($result->synonyms),
            'common_misunderstanding' => $result->commonMisunderstanding,
            'natural_example' => $result->naturalExample,
            'cefr_level' => $result->cefrLevel,
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
