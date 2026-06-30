<?php

namespace App\Services\Intelligence\Providers;

use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Exceptions\AiInvalidResponseException;
use App\Services\Intelligence\Exceptions\AiProviderException;
use App\Services\Intelligence\Explanation\ExplanationRequest;
use App\Services\Intelligence\Explanation\ExplanationResult;
use App\Services\Intelligence\Translation\TranslationRequest;
use App\Services\Intelligence\Translation\TranslationResult;
use App\Services\Intelligence\Tts\TtsRequest;
use App\Services\Intelligence\Tts\TtsResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProviderInterface
{
    public function translate(TranslationRequest $request): TranslationResult
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new AiProviderException('OpenAI is not configured.');
        }

        $started = microtime(true);

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $request->model,
                'store' => false,
                'input' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a concise dictionary for language learners. '
                            . 'Translate exactly the selected word or phrase, which contains at most 10 words. '
                            . 'Use sentence_context only to disambiguate meaning; never translate or add surrounding context that is not part of the selection. '
                            . 'If the selection contains multiple words, explain the meaning of the complete phrase, not only one word inside it. '
                            . 'The translation and explanation must be in the learner native language. '
                            . 'Keep the translation natural but faithful and concise. '
                            . 'Return only the requested structure.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'word' => $request->text,
                            'sentence_context' => $request->context,
                            'source_language_locale' => $request->sourceLanguage,
                            'native_language_locale' => $request->targetLanguage,
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
            throw new AiInvalidResponseException('OpenAI returned an invalid translation response.');
        }

        $translation = trim((string) $result['translation']);
        $pronunciation = trim((string) $result['pronunciation']);

        if ($translation === '') {
            throw new AiInvalidResponseException('OpenAI returned an empty translation.');
        }

        $providerDuration = (int) round((microtime(true) - $started) * 1000);
        $usage = $payload['usage'] ?? [];

        return new TranslationResult(
            text: $translation,
            provider: 'openai',
            model: $request->model,
            inputTokens: (int) ($usage['input_tokens'] ?? 0),
            outputTokens: (int) ($usage['output_tokens'] ?? 0),
            cachedInputTokens: (int) ($usage['cached_input_tokens'] ?? 0),
            rawUsage: $usage,
            providerDurationMs: $providerDuration,
            pronunciation: $pronunciation,
        );
    }

    public function explain(ExplanationRequest $request): ExplanationResult
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new AiProviderException('OpenAI is not configured.');
        }

        $started = microtime(true);

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $request->model,
                'store' => false,
                'input' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a concise dictionary for language learners. '
                            . 'Explain the selected word or phrase in the learner native language. '
                            . 'Use the sentence context to disambiguate meaning. '
                            . 'Keep the explanation natural but faithful and concise. '
                            . 'Return only the requested structure.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'word' => $request->selectedText,
                            'sentence_context' => $request->context,
                            'source_language_locale' => $request->sourceLanguage,
                            'native_language_locale' => $request->targetLanguage,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'word_explanation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'explanation' => ['type' => 'string'],
                                'examples' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'required' => ['explanation'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'max_output_tokens' => 300,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'output.0.content.0.text');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result) || ! isset($result['explanation'])) {
            throw new AiInvalidResponseException('OpenAI returned an invalid explanation response.');
        }

        $explanation = trim((string) $result['explanation']);

        if ($explanation === '') {
            throw new AiInvalidResponseException('OpenAI returned an empty explanation.');
        }

        $providerDuration = (int) round((microtime(true) - $started) * 1000);
        $usage = $payload['usage'] ?? [];

        return new ExplanationResult(
            explanation: $explanation,
            examples: isset($result['examples']) ? (array) $result['examples'] : null,
            provider: 'openai',
            model: $request->model,
            inputTokens: (int) ($usage['input_tokens'] ?? 0),
            outputTokens: (int) ($usage['output_tokens'] ?? 0),
            providerDurationMs: $providerDuration,
        );
    }

    public function synthesize(TtsRequest $request): TtsResult
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new AiProviderException('OpenAI is not configured.');
        }

        $started = microtime(true);

        $response = Http::withToken($apiKey)
            ->accept('audio/mpeg')
            ->timeout(30)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => $request->model,
                'voice' => $request->voice,
                'input' => $request->text,
                'instructions' => sprintf(
                    'Speak naturally and warmly in locale %s. Use clear pronunciation, '
                    . 'gentle expression, and the unhurried pace of a skilled language teacher. '
                    . 'Do not sound theatrical or robotic.',
                    $request->language,
                ),
                'response_format' => $request->format,
            ]);

        $response->throw();

        $body = $response->body();

        if ($body === '') {
            throw new AiInvalidResponseException('OpenAI returned an empty TTS response.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tts-') . '.' . $request->format;
        file_put_contents($tempPath, $body);

        $providerDuration = (int) round((microtime(true) - $started) * 1000);

        return new TtsResult(
            temporaryFilePath: $tempPath,
            format: $request->format,
            fileSizeBytes: strlen($body),
            providerDurationMs: $providerDuration,
        );
    }
}
