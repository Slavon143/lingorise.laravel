<?php

namespace App\Services\Intelligence\Explanation;

class ContextExplanationResult
{
    public function __construct(
        public readonly string $expression,
        public readonly string $meaningInContext,
        public readonly string $whyThisMeaning,
        public readonly ?string $roleInSentence,
        public readonly ?string $baseForm,
        public readonly ?string $partOfSpeech,
        public readonly bool $fixedExpression,
        public readonly ?string $literalTranslationWarning,
        public readonly ?string $register,
        public readonly ?string $connotation,
        public readonly array $synonyms,
        public readonly ?string $commonMisunderstanding,
        public readonly ?string $naturalExample,
        public readonly ?string $cefrLevel,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $providerDurationMs = 0,
    ) {}
}
