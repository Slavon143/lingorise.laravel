<?php

namespace App\Services\Intelligence\Cache;

use App\Services\ContentHashService;

class AiCacheKeyFactory
{
    public function __construct(
        private readonly ContentHashService $hashes,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): string
    {
        return $this->hashes->cacheKey('legacy', 'v1', $payload);
    }
}
