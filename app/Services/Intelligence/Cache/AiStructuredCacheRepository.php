<?php

namespace App\Services\Intelligence\Cache;

use App\Models\AiStructuredCache;
use App\Services\ContentHashService;

class AiStructuredCacheRepository
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly AiCacheKeyFactory $keyFactory,
        private readonly ContentHashService $hashes,
    ) {}

    public const array PROMPT_VERSIONS = [
        'context_explanation' => 3,
        'grammar_explanation' => 3,
        'simplification' => 4,
    ];

    public const array SCHEMA_VERSIONS = [
        'context_explanation' => 3,
        'grammar_explanation' => 3,
        'simplification' => 4,
    ];

    public function cacheKey(
        string $operationType,
        string $sourceText,
        string $sourceLanguage,
        string $targetLanguage,
        ?string $context = null,
        ?string $targetLevel = null,
        string $model = 'gpt-4o-mini',
        string $provider = 'openai',
        ?int $promptVersion = null,
        ?int $responseFormatVersion = null,
        ?string $privacyScope = null,
        ?int $scopeId = null,
    ): string {
        $promptVersion ??= self::PROMPT_VERSIONS[$operationType] ?? 1;
        $responseFormatVersion ??= self::SCHEMA_VERSIONS[$operationType] ?? 1;

        $payload = [
            'source_text_hash' => $this->hashes->hashText($sourceText),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => $promptVersion,
            'response_format_version' => $responseFormatVersion,
        ];

        if ($context !== null) {
            $payload['context_hash'] = $this->hashes->hashText($context);
        }

        if ($targetLevel !== null) {
            $payload['target_level'] = $targetLevel;
        }

        return $this->hashes->cacheKey($operationType, 'v' . $promptVersion . ':schema' . $responseFormatVersion, $payload);
    }

    public function find(string $cacheKey): ?AiStructuredCache
    {
        $entry = AiStructuredCache::where('cache_key', $cacheKey)->first();

        if ($entry) {
            $entry->increment('hits');
            $entry->forceFill(['last_used_at' => now()])->save();
        }

        return $entry;
    }

    public function store(
        string $cacheKey,
        string $operationType,
        string $sourceText,
        string $sourceLanguage,
        string $targetLanguage,
        array $responseJson,
        ?string $context = null,
        ?string $targetLevel = null,
        string $model = 'gpt-4o-mini',
        string $provider = 'openai',
        ?string $privacyScope = null,
        ?int $scopeId = null,
        ?int $originalUsageEventId = null,
    ): AiStructuredCache {
        $normalized = $this->normalizer->normalizeForCache($sourceText);
        $normalizedContext = $context ? $this->normalizer->normalizeForCache($context) : null;

        return AiStructuredCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'operation_type' => $operationType,
                'source_text' => $normalized,
                'source_text_hash' => $this->hashes->hashText($sourceText),
                'context_text' => $normalizedContext,
                'context_hash' => $context ? $this->hashes->hashText($context) : null,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'target_level' => $targetLevel,
                'response_json' => $responseJson,
                'model' => $model,
                'provider' => $provider,
                'prompt_version' => self::PROMPT_VERSIONS[$operationType] ?? 1,
                'response_format_version' => self::SCHEMA_VERSIONS[$operationType] ?? 1,
                'privacy_scope' => $privacyScope,
                'scope_id' => $scopeId,
                'original_usage_event_id' => $originalUsageEventId,
            ],
        );
    }
}
