<?php

namespace App\Services\Intelligence\Cache;

use App\Models\AiStructuredCache;

class AiStructuredCacheRepository
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly AiCacheKeyFactory $keyFactory,
    ) {}

    public const int PROMPT_VERSION = 1;

    public const int RESPONSE_FORMAT_VERSION = 1;

    public function cacheKey(
        string $operationType,
        string $sourceText,
        string $sourceLanguage,
        string $targetLanguage,
        ?string $context = null,
        ?string $targetLevel = null,
        string $model = 'gpt-4o-mini',
        string $provider = 'openai',
        int $promptVersion = self::PROMPT_VERSION,
        int $responseFormatVersion = self::RESPONSE_FORMAT_VERSION,
        ?string $privacyScope = null,
        ?int $scopeId = null,
    ): string {
        $payload = [
            'operation' => $operationType,
            'source_text' => $this->normalizer->normalizeForCache($sourceText),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => $promptVersion,
            'response_format_version' => $responseFormatVersion,
        ];

        if ($context !== null) {
            $payload['context'] = $this->normalizer->normalizeForCache($context);
        }

        if ($targetLevel !== null) {
            $payload['target_level'] = $targetLevel;
        }

        if ($privacyScope !== null) {
            $payload['privacy_scope'] = $privacyScope;
            $payload['scope_id'] = $scopeId;
        }

        return $this->keyFactory->create($payload);
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
                'source_text_hash' => sha1($normalized),
                'context_text' => $normalizedContext,
                'context_hash' => $normalizedContext ? sha1($normalizedContext) : null,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'target_level' => $targetLevel,
                'response_json' => $responseJson,
                'model' => $model,
                'provider' => $provider,
                'prompt_version' => self::PROMPT_VERSION,
                'response_format_version' => self::RESPONSE_FORMAT_VERSION,
                'privacy_scope' => $privacyScope,
                'scope_id' => $scopeId,
                'original_usage_event_id' => $originalUsageEventId,
            ],
        );
    }
}
