<?php

namespace App\Services\Intelligence\Usage;

class AiUsageContext
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly ?int $bookId = null,
        public readonly ?string $ipHash = null,
        public readonly ?string $userAgentHash = null,
    ) {}
}
