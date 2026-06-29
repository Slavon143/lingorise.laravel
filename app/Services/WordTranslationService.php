<?php

namespace App\Services;

use App\Services\Ai\AiCacheKey;
use App\Services\Ai\AiUsageLogger;
use App\Services\Ai\ExplanationCacheService;
use App\Services\Ai\TranslationCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WordTranslationService
{
    public function __construct(
        private readonly AiCacheKey $keys,
        private readonly AiUsageLogger $usageLogger,
        private readonly ExplanationCacheService $explanations,
        private readonly TranslationCacheService $translations,
    ) {}

    public function translate(
        string $word,
        string $context,
        string $sourceLocale,
        string $nativeLocale,
        ?int $userId = null,
        string $privacyScope = 'private',
        ?int $scopeId = null,
    ): array {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('Translation service is not configured.');
        }

        $started = microtime(true);
        $model = config('services.openai.model', 'gpt-5.4-mini');
        $normalizedWord = $this->keys->normalizeText($word);
        $normalizedContext = $this->keys->normalizeText($context);
        $translationKey = $this->translations->cacheKey($normalizedWord, $sourceLocale, $nativeLocale, $model, $privacyScope, $scopeId);
        $explanationKey = $this->explanations->cacheKey($normalizedWord, $normalizedContext, $sourceLocale, $nativeLocale, $model, $privacyScope, $scopeId);

        $translation = $this->translations->find($translationKey);
        $explanation = $this->explanations->find($explanationKey);

        if ($translation && $explanation) {
            $this->usageLogger->log($userId, 'translation', $translationKey, mb_strlen($normalizedWord), true, $model, $this->duration($started), 'ok');
            $this->usageLogger->log($userId, 'explanation', $explanationKey, mb_strlen($normalizedContext), true, $model, $this->duration($started), 'ok');

            return [
                'translation' => $translation->translated_text,
                'pronunciation' => $translation->pronunciation ?? '',
                'explanation' => $explanation->explanation_text,
            ];
        }

        return Cache::lock('ai-cache:translation:'.$translationKey.':'.$explanationKey, 45)->block(15, function () use (
            $apiKey,
            $explanation,
            $explanationKey,
            $model,
            $nativeLocale,
            $normalizedContext,
            $normalizedWord,
            $sourceLocale,
            $started,
            $translation,
            $translationKey,
            $userId,
        ): array {
            $translation ??= $this->translations->find($translationKey);
            $explanation ??= $this->explanations->find($explanationKey);

            if ($translation && $explanation) {
                $this->usageLogger->log($userId, 'translation', $translationKey, mb_strlen($normalizedWord), true, $model, $this->duration($started), 'ok');
                $this->usageLogger->log($userId, 'explanation', $explanationKey, mb_strlen($normalizedContext), true, $model, $this->duration($started), 'ok');

                return [
                    'translation' => $translation->translated_text,
                    'pronunciation' => $translation->pronunciation ?? '',
                    'explanation' => $explanation->explanation_text,
                ];
            }

            try {
                $result = $this->requestTranslation($apiKey, $normalizedWord, $normalizedContext, $sourceLocale, $nativeLocale, $model);

                $translation = $this->translations->store(
                    $translationKey,
                    $normalizedWord,
                    $sourceLocale,
                    $nativeLocale,
                    $result['translation'],
                    $result['pronunciation'],
                    $model,
                );
                $explanation = $this->explanations->store(
                    $explanationKey,
                    $normalizedWord,
                    $normalizedContext,
                    $sourceLocale,
                    $nativeLocale,
                    $result['explanation'],
                    $model,
                );

                $this->usageLogger->log($userId, 'translation', $translationKey, mb_strlen($normalizedWord), false, $model, $this->duration($started), 'ok');
                $this->usageLogger->log($userId, 'explanation', $explanationKey, mb_strlen($normalizedContext), false, $model, $this->duration($started), 'ok');

                return [
                    'translation' => $translation->translated_text,
                    'pronunciation' => $translation->pronunciation ?? '',
                    'explanation' => $explanation->explanation_text,
                ];
            } catch (Throwable $exception) {
                $this->usageLogger->log($userId, 'translation', $translationKey, mb_strlen($normalizedWord), false, $model, $this->duration($started), 'error', $exception::class);
                $this->usageLogger->log($userId, 'explanation', $explanationKey, mb_strlen($normalizedContext), false, $model, $this->duration($started), 'error', $exception::class);

                throw $exception;
            }
        });
    }

    /**
     * @return array{translation: string, pronunciation: string, explanation: string}
     */
    private function requestTranslation(string $apiKey, string $word, string $context, string $sourceLocale, string $nativeLocale, string $model): array
    {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'store' => false,
                'input' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a concise dictionary for language learners. Translate exactly the selected word or phrase, which contains at most 10 words. Use sentence_context only to disambiguate meaning; never translate or add surrounding context that is not part of the selection. If the selection contains multiple words, explain the meaning of the complete phrase, not only one word inside it. The translation and explanation must be in the learner native language. Keep the translation natural but faithful and concise. Return only the requested structure.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'word' => $word,
                            'sentence_context' => $context,
                            'source_language_locale' => $sourceLocale,
                            'native_language_locale' => $nativeLocale,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'word_translation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'translation' => ['type' => 'string'],
                                'pronunciation' => ['type' => 'string'],
                                'explanation' => ['type' => 'string'],
                            ],
                            'required' => ['translation', 'pronunciation', 'explanation'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'max_output_tokens' => 180,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'output.0.content.0.text');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result) || ! isset($result['translation'], $result['pronunciation'], $result['explanation'])) {
            throw new RuntimeException('Translation service returned an invalid response.');
        }

        $translation = trim((string) $result['translation']);
        $pronunciation = trim((string) $result['pronunciation']);
        $explanation = trim((string) $result['explanation']);

        if ($translation === '' || $explanation === '') {
            throw new RuntimeException('Translation service returned an empty response.');
        }

        return [
            'translation' => $translation,
            'pronunciation' => $pronunciation,
            'explanation' => $explanation,
        ];
    }

    private function duration(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
