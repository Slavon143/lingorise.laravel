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
                        'content' => 'You are a concise dictionary for language learners. '
                            . 'Given a word or short phrase, its sentence context, the source language, and the user\'s native language: '
                            . '1. Explain the meaning specifically IN THIS CONTEXT. '
                            . '2. Provide the base form (lemma) if applicable. '
                            . '3. Identify the part of speech. '
                            . '4. Translate the word/phrase into the user\'s native language. '
                            . '5. Give a simple explanation suitable for language learners. '
                            . '6. Provide one short example sentence showing usage. '
                            . '7. Estimate the CEFR level (A1-C2). '
                            . '8. If the word has a grammatical form (e.g., past tense, plural), note it. '
                            . '9. If the word is part of a fixed expression, provide the full expression. '
                            . 'Return only valid JSON with no additional text.',
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
                'max_completion_tokens' => 400,
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
            meaningInContext: trim((string) ($result['meaning_in_context'] ?? '')),
            baseForm: isset($result['base_form']) ? trim((string) $result['base_form']) : null,
            partOfSpeech: isset($result['part_of_speech']) ? trim((string) $result['part_of_speech']) : null,
            translation: trim((string) ($result['translation'] ?? '')),
            simpleExplanation: trim((string) ($result['simple_explanation'] ?? '')),
            example: isset($result['example']) ? trim((string) $result['example']) : null,
            cefrLevel: isset($result['cefr_level']) ? trim((string) $result['cefr_level']) : null,
            grammarForm: isset($result['grammar_form']) ? trim((string) $result['grammar_form']) : null,
            fixedExpression: isset($result['fixed_expression']) ? trim((string) $result['fixed_expression']) : null,
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
                'response_format' => ['type' => 'json_object'],
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
            parts: $result['parts'] ?? [],
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
                            . '7. List the main replacements made. '
                            . '8. Briefly explain what changed. '
                            . '9. If the meaning had to be adapted approximately, set meaning_adapted to true and explain. '
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
                'response_format' => ['type' => 'json_object'],
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
