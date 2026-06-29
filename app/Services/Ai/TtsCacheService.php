<?php

namespace App\Services\Ai;

use App\Models\TtsCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TtsCacheService
{
    public const VERSION = 1;

    public function __construct(
        private readonly AiCacheKey $keys,
        private readonly AiUsageLogger $usageLogger,
    ) {}

    /**
     * @return array{body: string, cache_key: string, cache_hit: bool, file_path: string}
     */
    public function audio(
        string $text,
        string $language,
        int $userId,
        string $voice,
        string $model,
        string $format = 'mp3',
        string $speed = '1',
    ): array {
        $started = microtime(true);
        $normalizedText = $this->keys->normalizeText($text);
        $cacheKey = $this->cacheKey($normalizedText, $language, $voice, $speed, $model, $format, $userId);

        if ($cached = $this->findUsable($cacheKey)) {
            $this->usageLogger->log($userId, 'tts', $cacheKey, mb_strlen($normalizedText), true, $model, $this->duration($started), 'ok');

            return [
                'body' => Storage::disk('local')->get($cached->file_path),
                'cache_key' => $cacheKey,
                'cache_hit' => true,
                'file_path' => $cached->file_path,
            ];
        }

        return Cache::lock('ai-cache:tts:'.$cacheKey, 45)->block(15, function () use (
            $cacheKey,
            $format,
            $language,
            $model,
            $normalizedText,
            $speed,
            $started,
            $userId,
            $voice,
        ): array {
            if ($cached = $this->findUsable($cacheKey)) {
                $this->usageLogger->log($userId, 'tts', $cacheKey, mb_strlen($normalizedText), true, $model, $this->duration($started), 'ok');

                return [
                    'body' => Storage::disk('local')->get($cached->file_path),
                    'cache_key' => $cacheKey,
                    'cache_hit' => true,
                    'file_path' => $cached->file_path,
                ];
            }

            try {
                $body = $this->generateAudio($normalizedText, $language, $voice, $model, $format);
                $path = $this->pathFor($cacheKey, $format);
                $this->writeAtomically($path, $body);

                TtsCache::updateOrCreate(
                    ['cache_key' => $cacheKey],
                    [
                        'source_text' => $normalizedText,
                        'language' => $language,
                        'voice' => $voice,
                        'speed' => $speed,
                        'model' => $model,
                        'format' => $format,
                        'file_path' => $path,
                        'file_size' => strlen($body),
                    ],
                );

                $this->usageLogger->log($userId, 'tts', $cacheKey, mb_strlen($normalizedText), false, $model, $this->duration($started), 'ok');

                return [
                    'body' => $body,
                    'cache_key' => $cacheKey,
                    'cache_hit' => false,
                    'file_path' => $path,
                ];
            } catch (Throwable $exception) {
                $this->usageLogger->log($userId, 'tts', $cacheKey, mb_strlen($normalizedText), false, $model, $this->duration($started), 'error', $exception::class);

                throw $exception;
            }
        });
    }

    private function cacheKey(string $text, string $language, string $voice, string $speed, string $model, string $format, int $userId): string
    {
        return $this->keys->hash([
            'type' => 'tts',
            'text' => $text,
            'language' => $language,
            'voice' => $voice,
            'speed' => $speed,
            'model' => $model,
            'format' => $format,
            'version' => self::VERSION,
            'privacy_scope' => 'user',
            'scope_id' => $userId,
        ]);
    }

    private function findUsable(string $cacheKey): ?TtsCache
    {
        $entry = TtsCache::where('cache_key', $cacheKey)->first();

        if (! $entry) {
            return null;
        }

        if (! Storage::disk('local')->exists($entry->file_path)) {
            $entry->delete();

            return null;
        }

        $entry->increment('hits');
        $entry->forceFill(['last_used_at' => now()])->save();

        return $entry;
    }

    private function generateAudio(string $text, string $language, string $voice, string $model, string $format): string
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('Natural voice is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->accept('audio/mpeg')
            ->timeout(30)
            ->retry(2, 250)
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => $model,
                'voice' => $voice,
                'input' => $text,
                'instructions' => sprintf(
                    'Speak naturally and warmly in locale %s. Use clear pronunciation, gentle expression, and the unhurried pace of a skilled language teacher. Do not sound theatrical or robotic.',
                    $language,
                ),
                'response_format' => $format,
            ]);

        $response->throw();

        $body = $response->body();

        if ($body === '') {
            throw new RuntimeException('TTS service returned an empty response.');
        }

        return $body;
    }

    private function pathFor(string $cacheKey, string $format): string
    {
        return sprintf(
            'private/tts/%s/%s/%s.%s',
            substr($cacheKey, 0, 2),
            substr($cacheKey, 2, 2),
            $cacheKey,
            $format,
        );
    }

    private function writeAtomically(string $path, string $body): void
    {
        $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(6));

        Storage::disk('local')->put($temporaryPath, $body);

        $absoluteTemporaryPath = Storage::disk('local')->path($temporaryPath);
        $absolutePath = Storage::disk('local')->path($path);
        $directory = dirname($absolutePath);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            Storage::disk('local')->delete($temporaryPath);
            throw new RuntimeException('Could not create TTS cache directory.');
        }

        if (! @rename($absoluteTemporaryPath, $absolutePath)) {
            Storage::disk('local')->delete($temporaryPath);
            throw new RuntimeException('Could not move TTS cache file into place.');
        }
    }

    private function duration(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
