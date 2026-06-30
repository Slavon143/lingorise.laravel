<?php

namespace App\Services\Intelligence\Tts;

class TtsRequest
{
    public function __construct(
        public readonly string $text,
        public readonly string $language,
        public readonly string $voice = 'marin',
        public readonly string $speed = '1',
        public readonly string $provider = 'openai',
        public readonly string $model = 'gpt-4o-mini-tts',
        public readonly string $format = 'mp3',
        public readonly ?int $sampleRate = null,
        public readonly int $version = 1,
    ) {}
}
