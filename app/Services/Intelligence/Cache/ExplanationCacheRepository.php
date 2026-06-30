<?php

namespace App\Services\Intelligence\Cache;

use App\Models\ExplanationCache;

class ExplanationCacheRepository
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly AiCacheKeyFactory $keyFactory,
    ) {}

    public const int PROMPT_VERSION = 1;

    public const int RESPONSE_FORMAT_VERSION = 1;

    public function cacheKey(
        string $selectedText,
        string $context,
        string $sourceLanguage,
        string $targetLanguage,
        string $model = 'gpt-5.4-mini',
        string $provider = 'openai',
        int $promptVersion = self::PROMPT_VERSION,
        ?string $mode = null,
        int $responseFormatVersion = self::RESPONSE_FORMAT_VERSION,
        ?string $privacyScope = null,
        ?int $scopeId = null,
    ): string {
        return $this->keyFactory->create([
            'operation' => 'explanation',
            'selected_text' => $this->normalizer->normalizeForCache($selectedText),
            'context' => $this->normalizer->normalizeForCache($context),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => $promptVersion,
            'mode' => $mode,
            'response_format_version' => $responseFormatVersion,
            'privacy_scope' => $privacyScope,
            'scope_id' => $scopeId,
        ]);
    }

    public function find(string $cacheKey): ?ExplanationCache
    {
        $entry = ExplanationCache::where('cache_key', $cacheKey)->first();

        if ($entry) {
            $entry->increment('hits');
            $entry->forceFill(['last_used_at' => now()])->save();
        }

        return $entry;
    }

    public function store(
        string $cacheKey,
        string $selectedText,
        string $context,
        string $sourceLanguage,
        string $targetLanguage,
        string $explanationText,
        string $model = 'gpt-5.4-mini',
        string $provider = 'openai',
        ?string $mode = null,
        ?int $originalUsageEventId = null,
    ): ExplanationCache {
        $normalizedText = $this->normalizer->normalizeForCache($selectedText);
        $normalizedContext = $this->normalizer->normalizeForCache($context);

        return ExplanationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'selected_text' => $normalizedText,
                'selected_text_hash' => sha1($normalizedText),
                'context_text' => $normalizedContext,
                'context_hash' => sha1($normalizedContext),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'explanation_text' => trim($explanationText),
                'model' => $model,
                'provider' => $provider,
                'prompt_version' => self::PROMPT_VERSION,
                'mode' => $mode,
                'response_format_version' => self::RESPONSE_FORMAT_VERSION,
            ],
        );
    }
}
