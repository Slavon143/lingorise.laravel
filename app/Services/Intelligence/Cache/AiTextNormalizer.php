<?php

namespace App\Services\Intelligence\Cache;

class AiTextNormalizer
{
    public function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }

    public function normalizeForCache(string $text): string
    {
        return $this->normalize($text);
    }

    public function normalizeForTts(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        return trim($text);
    }
}
