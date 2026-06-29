<?php

namespace App\Services\Ai;

use App\Models\ExplanationCache;

class ExplanationCacheService
{
    public const PROMPT_VERSION = 1;

    public function __construct(private readonly AiCacheKey $keys) {}

    public function cacheKey(
        string $selectedText,
        string $context,
        string $sourceLanguage,
        string $targetLanguage,
        string $model,
        string $privacyScope,
        ?int $scopeId,
    ): string {
        return $this->keys->hash([
            'type' => 'explanation',
            'selected_text' => $this->keys->normalizeText($selectedText),
            'context' => $this->keys->normalizeText($context),
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'model' => $model,
            'prompt_version' => self::PROMPT_VERSION,
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
        string $model,
    ): ExplanationCache {
        return ExplanationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'selected_text' => $this->keys->normalizeText($selectedText),
                'context_text' => $this->keys->normalizeText($context),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'explanation_text' => trim($explanationText),
                'model' => $model,
                'prompt_version' => self::PROMPT_VERSION,
            ],
        );
    }
}
