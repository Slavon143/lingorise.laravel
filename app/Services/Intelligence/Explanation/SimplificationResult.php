<?php

namespace App\Services\Intelligence\Explanation;

class SimplificationResult
{
    public function __construct(
        public readonly string $original,
        public readonly string $simplified,
        public readonly string $targetLevel,
        public readonly bool $isFragment,
        public readonly bool $meaningPreserved,
        public readonly array $replacements,
        public readonly ?string $explanation,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $providerDurationMs = 0,
    ) {}
}
