<?php

namespace App\Services\Ai;

class AiCacheKey
{
    public function normalizeText(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hash(array $payload): string
    {
        $payload = $this->sortKeys($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function sortKeys(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortKeys($item);
            }
        }

        return $value;
    }
}
