<?php

namespace App\Services\Intelligence\Explanation;

class ExplanationRequest
{
    public function __construct(
        public readonly string $selectedText,
        public readonly string $context,
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly string $provider = 'openai',
        public readonly string $model = 'gpt-5.4-mini',
        public readonly int $promptVersion = 1,
        public readonly ?string $mode = null,
        public readonly int $responseFormatVersion = 1,
        public readonly ?string $privacyScope = null,
        public readonly ?int $scopeId = null,
    ) {}
}
