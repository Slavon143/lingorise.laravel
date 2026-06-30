<?php

namespace App\Services\Intelligence\Explanation;

class ExplanationResult
{
    public function __construct(
        public readonly string $explanation,
        public readonly ?array $examples = null,
        public readonly string $provider = 'openai',
        public readonly string $model = 'gpt-5.4-mini',
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly ?int $providerDurationMs = null,
    ) {}
}
