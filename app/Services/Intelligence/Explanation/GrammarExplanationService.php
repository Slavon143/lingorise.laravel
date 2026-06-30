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

class GrammarExplanationService
{
    public function __construct(
        private readonly AiStructuredCacheRepository $cache,
        private readonly AiProviderInterface $provider,
        private readonly AiUsageRecorder $usageRecorder,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiBudgetGuard $budget,
        private readonly GrammarPromptFactory $promptFactory,
    ) {}

    public function explain(
        string $text,
        ?string $context,
        string $sourceLanguage,
        string $targetLanguage,
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $privacyScope = $book?->isPublic() ? 'public' : 'book';
        $scopeId = $book?->isPublic() ? null : $book?->id;

        $cacheKey = $this->cache->cacheKey(
            operationType: 'grammar_explanation',
            sourceText: $text,
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
                'operation_type' => AiOperationType::GrammarExplanation->value,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'provider_called' => false,
                'request_characters' => mb_strlen($text) + (int) mb_strlen($context ?? ''),
                'response_characters' => mb_strlen(json_encode($cached->response_json)),
                'cost_calculation_type' => CostCalculationType::CacheReference->value,
                'status' => AiUsageStatus::Success->value,
                'ip_hash' => $usageContext?->ipHash,
                'user_agent_hash' => $usageContext?->userAgentHash,
            ]);

            return $this->buildResponse($cached->response_json, true, $cacheKey);
        }

        $lockKey = 'ai:grammar_explanation:' . $cacheKey;

        return Cache::lock($lockKey, 45)->block(15, function () use (
            $book, $cacheKey, $context, $text,
            $sourceLanguage, $targetLanguage, $userId, $usageContext,
        ): array {
            $cached = $this->cache->find($cacheKey);

            if ($cached) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::GrammarExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($text) + (int) mb_strlen($context ?? ''),
                    'response_characters' => mb_strlen(json_encode($cached->response_json)),
                    'cost_calculation_type' => CostCalculationType::CacheReference->value,
                    'status' => AiUsageStatus::Success->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                return $this->buildResponse($cached->response_json, true, $cacheKey);
            }

            if (! $this->budget->isAllowed(AiOperationType::GrammarExplanation)) {
                $this->usageRecorder->log([
                    'request_uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'book_id' => $book?->id,
                    'operation_type' => AiOperationType::GrammarExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => false,
                    'request_characters' => mb_strlen($text) + (int) mb_strlen($context ?? ''),
                    'cost_calculation_type' => CostCalculationType::Unknown->value,
                    'status' => AiUsageStatus::BudgetBlocked->value,
                    'ip_hash' => $usageContext?->ipHash,
                    'user_agent_hash' => $usageContext?->userAgentHash,
                ]);

                throw new AiBudgetExceededException('Grammar explanation budget exceeded.');
            }

            try {
                $started = microtime(true);

                $request = new GrammarExplanationData(
                    text: $text,
                    context: $context,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                );

                Log::debug('GrammarExplanationService: calling provider', [
                    'text' => mb_substr($text, 0, 100),
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                ]);

                $result = $this->provider->explainGrammar($request);

                Log::debug('GrammarExplanationService: provider returned', [
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'provider_duration_ms' => $result->providerDurationMs,
                ]);

                $responseJson = $this->validateResponse($result);

                $this->cache->store(
                    cacheKey: $cacheKey,
                    operationType: 'grammar_explanation',
                    sourceText: $text,
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
                    'operation_type' => AiOperationType::GrammarExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($text) + (int) mb_strlen($context ?? ''),
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
                    'operation_type' => AiOperationType::GrammarExplanation->value,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'provider_called' => true,
                    'request_characters' => mb_strlen($text) + (int) mb_strlen($context ?? ''),
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

    private function validateResponse(GrammarExplanationResult $result): array
    {
        $construction = trim($result->construction);
        $purpose = trim($result->purpose);

        if ($construction === '' || $purpose === '') {
            throw new AiInvalidResponseException('Grammar explanation returned empty required fields.');
        }

        if (mb_strlen($construction) > 2000 || mb_strlen($purpose) > 5000) {
            throw new AiInvalidResponseException('Grammar explanation field exceeds maximum length.');
        }

        return [
            'construction' => $construction,
            'purpose' => $purpose,
            'structure' => $result->structure ? trim($result->structure) : null,
            'parts' => array_values($result->parts),
            'simplified_translation' => $result->simplifiedTranslation,
            'additional_example' => $result->additionalExample ? trim($result->additionalExample) : null,
            'common_mistake' => $result->commonMistake ? trim($result->commonMistake) : null,
            'language_strategy' => $result->languageStrategy,
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
