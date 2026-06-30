<?php

namespace App\Services\Intelligence\Cache;

use App\Models\TranslationCache;
use App\Services\ContentHashService;

class TranslationCacheRepository
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly AiCacheKeyFactory $keyFactory,
        private readonly ContentHashService $hashes,
    ) {}

    public const int PROMPT_VERSION = 2;

    public const int RESPONSE_FORMAT_VERSION = 1;

    /**
     * @param  array<string, mixed>  $extra
     */
    public function cacheKey(
        string $text,
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
        return $this->hashes->cacheKey('translation', 'v' . $promptVersion . ':schema' . $responseFormatVersion, [
            'text_hash' => $this->hashes->hashText($text),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => $promptVersion,
            'mode' => $mode,
            'response_format_version' => $responseFormatVersion,
        ]);
    }

    public function find(string $cacheKey): ?TranslationCache
    {
        $entry = TranslationCache::where('cache_key', $cacheKey)->first();

        if ($entry) {
            $entry->increment('hits');
            $entry->forceFill(['last_used_at' => now()])->save();
        }

        return $entry;
    }

    public function store(
        string $cacheKey,
        string $sourceText,
        string $sourceLanguage,
        string $targetLanguage,
        string $translatedText,
        string $pronunciation,
        string $model = 'gpt-5.4-mini',
        string $provider = 'openai',
        ?string $mode = null,
        ?int $originalUsageEventId = null,
    ): TranslationCache {
        $normalized = $this->normalizer->normalizeForCache($sourceText);

        return TranslationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'source_text' => $normalized,
                'source_text_hash' => $this->hashes->hashText($sourceText),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'translated_text' => trim($translatedText),
                'pronunciation' => trim($pronunciation),
                'model' => $model,
                'provider' => $provider,
                'prompt_version' => self::PROMPT_VERSION,
                'mode' => $mode,
                'response_format_version' => self::RESPONSE_FORMAT_VERSION,
                'source_characters' => mb_strlen($normalized),
                'response_characters' => mb_strlen(trim($translatedText)),
                'original_usage_event_id' => $originalUsageEventId,
            ],
        );
    }
}
