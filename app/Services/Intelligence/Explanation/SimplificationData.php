<?php

namespace App\Services\Intelligence\Explanation;

class SimplificationData
{
    public function __construct(
        public readonly string $text,
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly string $targetLevel,
        public readonly bool $preserveStyle = false,
        public readonly ?string $validationFeedback = null,
        public readonly string $provider = 'openai',
        public readonly string $model = 'gpt-4o-mini',
    ) {}
}
