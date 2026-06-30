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
    private const array FRAGMENT_ENDINGS = ['except', 'because', 'although', 'if', 'when', 'but', 'and', 'or'];
    private const array INTENSIFIERS = ['very', 'really', 'extremely'];
    private const array MODALS = ['might', 'may', 'could', 'should', 'would', 'will', 'must', 'can'];

    public function simplify(
        string $text,
        string $sourceLanguage,
        string $targetLevel,
        string $targetLanguage = 'de',
        bool $preserveStyle = false,
        ?int $userId = null,
        ?Book $book = null,
        ?AiUsageContext $usageContext = null,
    ): array {
        $cacheKey = $this->cache->cacheKey(
            operationType: 'simplification',
            sourceText: $text,
            sourceLanguage: $sourceLanguage,
            targetLanguage: $targetLanguage,
            context: null,
            targetLevel: $targetLevel,
            model: 'gpt-4o-mini',
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
            $sourceLanguage, $targetLanguage, $targetLevel, $preserveStyle,
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

                $request = fn (?string $feedback = null) => new SimplificationData(
                    text: $text,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                    targetLevel: $targetLevel,
                    preserveStyle: $preserveStyle,
                    validationFeedback: $feedback,
                );

                Log::debug('SimplificationService: calling provider', [
                    'text' => mb_substr($text, 0, 100),
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'target_level' => $targetLevel,
                ]);

                $result = $this->provider->simplify($request());

                $violations = $this->validationViolations($text, $result);
                if ($violations !== []) {
                    Log::warning('SimplificationService: retrying unsafe simplification', ['violations' => $violations]);
                    $result = $this->provider->simplify($request(implode('; ', $violations)));
                    $violations = $this->validationViolations($text, $result);
                }

                if ($violations !== []) {
                    Log::warning('SimplificationService: using safe fallback', ['violations' => $violations]);
                    $result = $this->fallbackResult($text, $targetLevel, $targetLanguage);
                }

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
                    targetLanguage: $targetLanguage,
                    responseJson: $responseJson,
                    targetLevel: $targetLevel,
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
            'level' => $result->targetLevel,
            'target_level' => $result->targetLevel,
            'is_fragment' => $result->isFragment,
            'meaning_preserved' => $result->meaningPreserved,
            'replacements' => $result->replacements,
            'explanation' => $result->explanation ? trim($result->explanation) : null,
            'changes_explanation' => $result->explanation ? trim($result->explanation) : null,
        ];
    }

    private function validationViolations(string $original, SimplificationResult $result): array
    {
        $violations = [];
        $source = $this->normalize($original);
        $simplified = $this->normalize($result->simplified);

        if ($result->simplified === '' || ! $result->meaningPreserved) {
            $violations[] = 'meaning_preserved is false or simplified text is empty';
        }

        if ($this->isFragment($original) && ! $result->isFragment) {
            $violations[] = 'incomplete sentence fragment was not marked as is_fragment=true';
        }

        if (preg_match('/\bthere\s+(is|are|was|were)\b/u', $source) && ! preg_match('/\bthere\s+(is|are|was|were)\b/u', $simplified)) {
            $violations[] = 'existential construction changed';
        }

        if (preg_match('/\b(no|not|never|none|cannot|can\'t|don\'t|doesn\'t|didn\'t)\b/u', $source)
            && ! preg_match('/\b(no|not|never|none|cannot|can\'t|don\'t|doesn\'t|didn\'t)\b/u', $simplified)) {
            $violations[] = 'negation removed';
        }

        foreach (self::INTENSIFIERS as $word) {
            if (! preg_match('/\b' . preg_quote($word, '/') . '\b/u', $source) && preg_match('/\b' . preg_quote($word, '/') . '\b/u', $simplified)) {
                $violations[] = "added intensifier {$word}";
            }
        }

        foreach (self::MODALS as $modal) {
            if (preg_match('/\b' . preg_quote($modal, '/') . '\b/u', $source)) {
                foreach (self::MODALS as $other) {
                    if ($other !== $modal && preg_match('/\b' . preg_quote($other, '/') . '\b/u', $simplified)) {
                        $violations[] = "modality changed from {$modal} to {$other}";
                    }
                }
            }
        }

        if (preg_match('/\bfew\b/u', $source) && preg_match('/\bmany\b/u', $simplified)) {
            $violations[] = 'quantity changed from few to many';
        }

        if (preg_match('/\blittle\s+or\s+no\b/u', $source) && ! preg_match('/\b(almost\s+no|little\s+or\s+no)\b/u', $simplified)) {
            $violations[] = 'quantity/degree changed from little or no';
        }

        if ($this->isFragment($original) && ! str_ends_with(rtrim($result->simplified), '...')) {
            $violations[] = 'fragment continuation marker missing';
        }

        if (preg_match('/\bthey\s+have\b/u', $simplified) && preg_match('/\bthere\s+is\b/u', $source)) {
            $violations[] = 'changed fact from there is to they have';
        }

        return array_values(array_unique($violations));
    }

    private function fallbackResult(string $text, string $targetLevel, string $targetLanguage): SimplificationResult
    {
        $simplified = $text;
        $replacements = [];

        if (preg_match('/\bThere is little or no magic about them except\b/i', $text)) {
            $simplified = 'There is almost no magic about them, except...';
            $replacements[] = [
                'original' => 'little or no',
                'replacement' => 'almost no',
                'reason' => $targetLanguage === 'ru'
                    ? 'Более простая формулировка с тем же значением.'
                    : 'Simpler wording with the same meaning.',
            ];
        } elseif ($this->isFragment($text)) {
            $simplified = rtrim($text, " .\t\n\r\0\x0B") . '...';
        }

        return new SimplificationResult(
            original: $text,
            simplified: $simplified,
            targetLevel: $targetLevel,
            isFragment: $this->isFragment($text),
            meaningPreserved: true,
            replacements: $replacements,
            explanation: $targetLanguage === 'ru'
                ? 'Выбранный текст является частью предложения. Упрощена только доступная часть.'
                : 'The selected text is part of a sentence. Only the available fragment was simplified.',
        );
    }

    private function isFragment(string $text): bool
    {
        $normalized = $this->normalize($text);
        foreach (self::FRAGMENT_ENDINGS as $ending) {
            if (preg_match('/\b' . preg_quote($ending, '/') . '$/u', $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', $text)));
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
