<?php

namespace App\Services\Intelligence\Providers;

use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Exceptions\AiInvalidResponseException;
use App\Services\Intelligence\Exceptions\AiProviderException;
use App\Services\Intelligence\Explanation\ContextExplanationData;
use App\Services\Intelligence\Explanation\ContextExplanationResult;
use App\Services\Intelligence\Explanation\ExplanationRequest;
use App\Services\Intelligence\Explanation\ExplanationResult;
use App\Services\Intelligence\Explanation\GrammarExplanationData;
use App\Services\Intelligence\Explanation\GrammarExplanationResult;
use App\Services\Intelligence\Explanation\GrammarPromptFactory;
use App\Services\Intelligence\Explanation\SimplificationData;
use App\Services\Intelligence\Explanation\SimplificationResult;
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

        if (! is_array($result) || ! isset($result['translation'], $result['pronunciation']) || ! array_key_exists('explanation', $result)) {
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
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $request->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a concise dictionary for language learners. '
                            . 'Explain the selected word or phrase in the learner native language. '
                            . 'Use the sentence context to disambiguate meaning. '
                            . 'Keep the explanation natural but faithful and concise. '
                            . 'Return only valid JSON with "explanation" (string) and "examples" (array of strings).',
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
                'response_format' => ['type' => 'json_object'],
                'max_completion_tokens' => 300,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'choices.0.message.content');
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
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
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

    public function explainInContext(ContextExplanationData $request): ContextExplanationResult
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
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $request->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a concise language tutor explaining usage in context. '
                            . 'The user already sees the full translation separately. '
                            . 'Do not repeat the full translation of the selected sentence. '
                            . 'Return all explanations in the learner\'s native language: ' . $request->targetLanguage . '. '
                            . 'Keep the selected expression, synonyms and example sentence in the original source language when useful. '
                            . 'Explain: '
                            . '1. What the selected word or expression means specifically here (meaning_in_context). '
                            . '2. Why that meaning fits this sentence (why_this_meaning). '
                            . '3. Its grammatical role in the sentence (role_in_sentence). '
                            . '4. The base form (base_form) if it differs. '
                            . '5. The part of speech (part_of_speech). '
                            . '6. Whether it is a fixed expression, collocation, or idiom (fixed_expression). '
                            . '7. Whether literal word-by-word translation would be misleading (literal_translation_warning). '
                            . '8. Its register (register), e.g. formal, informal, literary. '
                            . '9. Its connotation (connotation). '
                            . '10. Two or three close synonyms (synonyms). '
                            . '11. One common misunderstanding (common_misunderstanding). '
                            . '12. One short natural example (natural_example). '
                            . '13. Estimate the CEFR level (cefr_level, A1-C2). '
                            . 'Be concise. Do not repeat the same idea in multiple fields. '
                            . 'Return only valid JSON with no additional text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'selected_text' => $request->selectedText,
                            'sentence_context' => $request->context,
                            'source_language' => $request->sourceLanguage,
                            'native_language' => $request->targetLanguage,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'context_explanation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'expression' => ['type' => 'string'],
                                'meaning_in_context' => ['type' => 'string'],
                                'why_this_meaning' => ['type' => 'string'],
                                'role_in_sentence' => ['type' => 'string'],
                                'base_form' => ['type' => 'string'],
                                'part_of_speech' => ['type' => 'string'],
                                'fixed_expression' => ['type' => 'boolean'],
                                'literal_translation_warning' => ['type' => 'string'],
                                'register' => ['type' => 'string'],
                                'connotation' => ['type' => 'string'],
                                'synonyms' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'common_misunderstanding' => ['type' => 'string'],
                                'natural_example' => ['type' => 'string'],
                                'cefr_level' => ['type' => 'string'],
                            ],
                            'required' => ['expression', 'meaning_in_context', 'why_this_meaning'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'max_output_tokens' => 500,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'choices.0.message.content');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result)) {
            throw new AiInvalidResponseException('OpenAI returned an invalid context explanation response.');
        }

        $providerDuration = (int) round((microtime(true) - $started) * 1000);
        $usage = $payload['usage'] ?? [];

        return new ContextExplanationResult(
            expression: trim((string) ($result['expression'] ?? '')),
            meaningInContext: trim((string) ($result['meaning_in_context'] ?? '')),
            whyThisMeaning: trim((string) ($result['why_this_meaning'] ?? '')),
            roleInSentence: isset($result['role_in_sentence']) ? trim((string) $result['role_in_sentence']) : null,
            baseForm: isset($result['base_form']) ? trim((string) $result['base_form']) : null,
            partOfSpeech: isset($result['part_of_speech']) ? trim((string) $result['part_of_speech']) : null,
            fixedExpression: (bool) ($result['fixed_expression'] ?? false),
            literalTranslationWarning: isset($result['literal_translation_warning']) ? trim((string) $result['literal_translation_warning']) : null,
            register: isset($result['register']) ? trim((string) $result['register']) : null,
            connotation: isset($result['connotation']) ? trim((string) $result['connotation']) : null,
            synonyms: isset($result['synonyms']) ? array_values((array) $result['synonyms']) : [],
            commonMisunderstanding: isset($result['common_misunderstanding']) ? trim((string) $result['common_misunderstanding']) : null,
            naturalExample: isset($result['natural_example']) ? trim((string) $result['natural_example']) : null,
            cefrLevel: isset($result['cefr_level']) ? trim((string) $result['cefr_level']) : null,
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
            providerDurationMs: $providerDuration,
        );
    }

    public function explainGrammar(GrammarExplanationData $request): GrammarExplanationResult
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new AiProviderException('OpenAI is not configured.');
        }

        $factory = new GrammarPromptFactory;
        $systemPrompt = $factory->systemPrompt($request->sourceLanguage, $request->targetLanguage);

        $started = microtime(true);

        $userContent = [
            'text' => $request->text,
            'source_language' => $request->sourceLanguage,
            'target_language' => $request->targetLanguage,
        ];

        if ($request->context !== null) {
            $userContent['context'] = $request->context;
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $request->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => json_encode($userContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'grammar_explanation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'construction' => ['type' => 'string'],
                                'purpose' => ['type' => 'string'],
                                'structure' => ['type' => 'string'],
                                'parts' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'text' => ['type' => 'string'],
                                            'role' => ['type' => 'string'],
                                        ],
                                        'required' => ['text', 'role'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                                'simplified_translation' => ['type' => 'string'],
                                'additional_example' => ['type' => 'string'],
                                'common_mistake' => ['type' => 'string'],
                            ],
                            'required' => ['construction', 'purpose', 'simplified_translation'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'max_completion_tokens' => 500,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'choices.0.message.content');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result)) {
            throw new AiInvalidResponseException('OpenAI returned an invalid grammar explanation response.');
        }

        $providerDuration = (int) round((microtime(true) - $started) * 1000);
        $usage = $payload['usage'] ?? [];

            return new GrammarExplanationResult(
                construction: trim((string) ($result['construction'] ?? '')),
                purpose: trim((string) ($result['purpose'] ?? '')),
                structure: isset($result['structure']) ? trim((string) $result['structure']) : null,
                parts: array_values((array) ($result['parts'] ?? [])),
            simplifiedTranslation: trim((string) ($result['simplified_translation'] ?? '')),
            additionalExample: isset($result['additional_example']) ? trim((string) $result['additional_example']) : null,
            commonMistake: isset($result['common_mistake']) ? trim((string) $result['common_mistake']) : null,
            languageStrategy: $factory->detectStrategy($request->sourceLanguage),
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
            providerDurationMs: $providerDuration,
        );
    }

    public function simplify(SimplificationData $request): SimplificationResult
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new AiProviderException('OpenAI is not configured.');
        }

        $started = microtime(true);

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 250)
                            ->post('https://api.openai.com/v1/chat/completions', [
                                'model' => $request->model,
                                'messages' => [
                                    [
                                        'role' => 'system',
                                        'content' => 'You are a text simplifier for language learners. '
                                            . 'Given a text in the source language and a target CEFR level (A1-C2): '
                                            . '1. Rewrite the text at the target level while preserving all facts, names, and events. '
                                            . '2. Do NOT add new information or events. '
                                            . '3. Do NOT remove important details. '
                                            . '4. Preserve names, places, and proper nouns exactly. '
                                            . '5. Do NOT turn literary text into a dry summary. '
                                            . '6. Do NOT spoil future chapters. '
                                            . '7. List the main replacements made (replacements), each with the original word/phrase, the replacement, and why it was changed. '
                                            . '8. Briefly explain what changed in the learner\'s native language (changes_explanation). '
                                            . '9. If the meaning had to be adapted approximately, set meaning_adapted to true and explain in the learner\'s native language (meaning_adapted_warning). '
                                            . 'The rewritten simplified text must stay in the source language. '
                                            . 'Return only valid JSON with no additional text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'text' => $request->text,
                            'source_language' => $request->sourceLanguage,
                            'target_level' => $request->targetLevel,
                            'preserve_style' => $request->preserveStyle,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'simplification',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'original' => ['type' => 'string'],
                                'simplified' => ['type' => 'string'],
                                'replacements' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                            'properties' => [
                                                'original' => ['type' => 'string'],
                                                'replacement' => ['type' => 'string'],
                                                'reason' => ['type' => 'string'],
                                            ],
                                            'required' => ['original', 'replacement', 'reason'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                                'changes_explanation' => ['type' => 'string'],
                                'meaning_adapted' => ['type' => 'boolean'],
                                'meaning_adapted_warning' => ['type' => 'string'],
                            ],
                            'required' => ['original', 'simplified'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'max_completion_tokens' => 800,
            ]);

        $response->throw();

        $payload = $response->json();
        $text = data_get($payload, 'choices.0.message.content');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result)) {
            throw new AiInvalidResponseException('OpenAI returned an invalid simplification response.');
        }

        $providerDuration = (int) round((microtime(true) - $started) * 1000);
        $usage = $payload['usage'] ?? [];

        return new SimplificationResult(
            original: trim((string) ($result['original'] ?? '')),
            simplified: trim((string) ($result['simplified'] ?? '')),
            targetLevel: $request->targetLevel,
            replacements: $result['replacements'] ?? [],
            changesExplanation: isset($result['changes_explanation']) ? trim((string) $result['changes_explanation']) : null,
            meaningAdapted: (bool) ($result['meaning_adapted'] ?? false),
            meaningAdaptedWarning: isset($result['meaning_adapted_warning']) ? trim((string) $result['meaning_adapted_warning']) : null,
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
            providerDurationMs: $providerDuration,
        );
    }
}
