<?php

namespace App\Services\Intelligence\Explanation;

class ContextExplanationData
{
    public function __construct(
        public readonly string $selectedText,
        public readonly string $context,
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly string $provider = 'openai',
        public readonly string $model = 'gpt-4o-mini',
    ) {}
}
