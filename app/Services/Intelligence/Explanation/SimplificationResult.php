<?php

namespace App\Services\Intelligence\Explanation;

class SimplificationResult
{
    public function __construct(
        public readonly string $original,
        public readonly string $simplified,
        public readonly string $targetLevel,
        public readonly array $replacements,
        public readonly ?string $changesExplanation,
        public readonly bool $meaningAdapted = false,
        public readonly ?string $meaningAdaptedWarning = null,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $providerDurationMs = 0,
    ) {}
}
