<?php

namespace App\Services\Intelligence\Cache;

use App\Enums\TtsCacheStatus;
use App\Models\TtsCache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TtsCacheRepository
{
    public function __construct(
        private readonly AiTextNormalizer $normalizer,
        private readonly AiCacheKeyFactory $keyFactory,
    ) {}

    public const int VERSION = 1;

    public function cacheKey(
        string $text,
        string $language,
        string $provider = 'openai',
        string $model = 'gpt-4o-mini-tts',
        string $voice = 'marin',
        string $speed = '1',
        string $format = 'mp3',
        ?int $sampleRate = null,
        int $version = self::VERSION,
    ): string {
        return $this->keyFactory->create([
            'operation' => 'tts',
            'text' => $this->normalizer->normalizeForTts($text),
            'language' => $language,
            'provider' => $provider,
            'model' => $model,
            'voice' => $voice,
            'speed' => (string) $speed,
            'format' => $format,
            'sample_rate' => $sampleRate,
            'version' => $version,
        ]);
    }

    public function findUsable(string $cacheKey): ?TtsCache
    {
        $entry = TtsCache::where('cache_key', $cacheKey)->first();

        if (! $entry) {
            return null;
        }

        if ($entry->status !== TtsCacheStatus::Ready->value) {
            return null;
        }

        if (! Storage::disk('local')->exists($entry->file_path)) {
            $entry->forceFill(['status' => TtsCacheStatus::Missing->value])->save();

            return null;
        }

        $entry->increment('hits');
        $entry->forceFill(['last_used_at' => now()])->save();

        return $entry;
    }

    public function markGenerating(string $cacheKey, string $text, array $params): TtsCache
    {
        return TtsCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            array_merge($params, [
                'source_text' => $this->normalizer->normalizeForTts($text),
                'file_path' => '',
                'status' => TtsCacheStatus::Generating->value,
                'generation_attempts' => 0,
            ]),
        );
    }

    public function markReady(string $cacheKey, string $filePath, int $fileSize, ?int $durationMs = null): void
    {
        TtsCache::where('cache_key', $cacheKey)->update([
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'duration_ms' => $durationMs,
            'status' => TtsCacheStatus::Ready->value,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $cacheKey, string $errorCode, string $errorMessage): void
    {
        TtsCache::where('cache_key', $cacheKey)->update([
            'status' => TtsCacheStatus::Failed->value,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    public function pathFor(string $cacheKey, string $format): string
    {
        return sprintf(
            'private/tts/%s/%s/%s.%s',
            substr($cacheKey, 0, 2),
            substr($cacheKey, 2, 2),
            $cacheKey,
            $format,
        );
    }

    public function writeAtomically(string $path, string $body): void
    {
        $temporaryPath = $path . '.tmp-' . bin2hex(random_bytes(6));

        Storage::disk('local')->put($temporaryPath, $body);

        $absoluteTemporaryPath = Storage::disk('local')->path($temporaryPath);
        $absolutePath = Storage::disk('local')->path($path);
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            Storage::disk('local')->delete($temporaryPath);
            throw new RuntimeException('Could not create TTS cache directory.');
        }

        if (!@rename($absoluteTemporaryPath, $absolutePath)) {
            Storage::disk('local')->delete($temporaryPath);
            throw new RuntimeException('Could not move TTS cache file into place.');
        }
    }
}
