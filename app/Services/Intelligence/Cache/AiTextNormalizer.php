<?php

namespace App\Services\Intelligence\Cache;

use App\Services\ContentHashService;

class AiTextNormalizer
{
    public function __construct(
        private readonly ContentHashService $hashes,
    ) {}

    public function normalize(string $text): string
    {
        return $this->hashes->normalize($text);
    }

    public function normalizeForCache(string $text): string
    {
        return $this->normalize($text);
    }

    public function normalizeForTts(string $text): string
    {
        return $this->hashes->normalize($text);
    }
}
