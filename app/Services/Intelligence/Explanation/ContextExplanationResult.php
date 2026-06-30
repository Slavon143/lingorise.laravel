<?php

namespace App\Services\Intelligence\Explanation;

class ContextExplanationResult
{
    public function __construct(
        public readonly string $meaningInContext,
        public readonly ?string $baseForm,
        public readonly ?string $partOfSpeech,
        public readonly string $translation,
        public readonly string $simpleExplanation,
        public readonly ?string $example,
        public readonly ?string $cefrLevel,
        public readonly ?string $grammarForm,
        public readonly ?string $fixedExpression,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $providerDurationMs = 0,
    ) {}
}
