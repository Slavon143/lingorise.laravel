<?php

namespace App\Services;

use Normalizer;

class ContentHashService
{
    public function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if (class_exists(Normalizer::class)) {
            return Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        }

        return $text;
    }

    public function hashText(string $text): string
    {
        return hash('sha256', $this->normalize($text));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function cacheKey(string $feature, string $version, array $payload): string
    {
        return hash('sha256', json_encode(
            $this->sortRecursively(['feature' => $feature, 'version' => $version] + $payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function sortRecursively(array $value): array
    {
        ksort($value);
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        return $value;
    }
}
