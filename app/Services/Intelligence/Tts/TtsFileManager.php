<?php

namespace App\Services\Intelligence\Tts;

use App\Services\Intelligence\Cache\TtsCacheRepository;
use RuntimeException;

class TtsFileManager
{
    public function __construct(
        private readonly TtsCacheRepository $cache,
    ) {}

    public function storeAudio(string $body, string $cacheKey, string $format): string
    {
        $path = $this->cache->pathFor($cacheKey, $format);
        $this->cache->writeAtomically($path, $body);

        return $path;
    }
}
