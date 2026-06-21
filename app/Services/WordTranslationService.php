<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WordTranslationService
{
    public function translate(string $word, string $context, string $sourceLocale, string $nativeLocale): array
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('Translation service is not configured.');
        }

        $cacheKey = 'word-translation:'.hash('sha256', implode('|', [
            mb_strtolower($word),
            $context,
            $sourceLocale,
            $nativeLocale,
        ]));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($apiKey, $word, $context, $sourceLocale, $nativeLocale): array {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->retry(2, 250)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-5.4-mini'),
                    'store' => false,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a concise dictionary for language learners. Use the sentence to choose the correct meaning. The translation and explanation must be in the learner native language. Return only the requested structure.',
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

            return [
                'translation' => trim((string) $result['translation']),
                'pronunciation' => trim((string) $result['pronunciation']),
                'explanation' => trim((string) $result['explanation']),
            ];
        });
    }
}
