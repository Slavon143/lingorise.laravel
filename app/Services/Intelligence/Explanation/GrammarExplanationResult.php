<?php

namespace App\Services\Intelligence\Explanation;

class GrammarExplanationResult
{
    public function __construct(
        public readonly string $construction,
        public readonly string $purpose,
        public readonly ?string $structure,
        public readonly array $parts,
        public readonly string $simplifiedTranslation,
        public readonly ?string $additionalExample,
        public readonly ?string $commonMistake,
        public readonly string $languageStrategy = 'generic',
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $providerDurationMs = 0,
    ) {}
}
