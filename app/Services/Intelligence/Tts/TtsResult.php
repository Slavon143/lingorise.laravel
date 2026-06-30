<?php

namespace App\Services\Intelligence\Tts;

class TtsResult
{
    public function __construct(
        public readonly string $temporaryFilePath,
        public readonly string $format,
        public readonly int $fileSizeBytes,
        public readonly ?int $durationMs = null,
        public readonly ?int $inputTokens = null,
        public readonly ?int $audioTokens = null,
        public readonly ?int $providerDurationMs = null,
    ) {}
}
