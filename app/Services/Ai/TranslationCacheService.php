<?php

namespace App\Services\Ai;

use App\Models\TranslationCache;

class TranslationCacheService
{
    public const PROMPT_VERSION = 1;

    public function __construct(private readonly AiCacheKey $keys) {}

    public function cacheKey(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $model,
        string $privacyScope,
        ?int $scopeId,
    ): string {
        return $this->keys->hash([
            'type' => 'translation',
            'text' => $this->keys->normalizeText($text),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'model' => $model,
            'prompt_version' => self::PROMPT_VERSION,
            'privacy_scope' => $privacyScope,
            'scope_id' => $scopeId,
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
        string $model,
    ): TranslationCache {
        return TranslationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'source_text' => $this->keys->normalizeText($sourceText),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'translated_text' => trim($translatedText),
                'pronunciation' => trim($pronunciation),
                'model' => $model,
                'prompt_version' => self::PROMPT_VERSION,
            ],
        );
    }
}
