<?php

namespace App\Services\Intelligence\Translation;

class TranslationResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cachedInputTokens = 0,
        public readonly ?array $rawUsage = null,
        public readonly ?int $providerDurationMs = null,
        public readonly ?string $pronunciation = null,
    ) {}
}
