<?php

namespace App\Console\Commands;

use App\Models\ExplanationCache;
use App\Models\TranslationCache;
use App\Models\TtsCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AiCacheCleanupCommand extends Command
{
    protected $signature = 'ai-cache:cleanup {--days=90} {--type=all : all, translation, explanation, or tts} {--dry-run}';

    protected $description = 'Clean old AI cache entries and private TTS files.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $type = (string) $this->option('type');
        $dryRun = (bool) $this->option('dry-run');
        $before = now()->subDays($days);

        if (! in_array($type, ['all', 'translation', 'explanation', 'tts'], true)) {
            $this->error('Invalid --type. Use all, translation, explanation, or tts.');

            return self::FAILURE;
        }

        $counts = [
            'translation' => 0,
            'explanation' => 0,
            'tts' => 0,
            'tts_files' => 0,
        ];

        if ($type === 'all' || $type === 'translation') {
            $query = TranslationCache::where(function ($query) use ($before): void {
                $query->whereNull('last_used_at')->where('created_at', '<', $before)
                    ->orWhere('last_used_at', '<', $before);
            });
            $counts['translation'] = (clone $query)->count();
            if (! $dryRun) {
                $query->delete();
            }
        }

        if ($type === 'all' || $type === 'explanation') {
            $query = ExplanationCache::where(function ($query) use ($before): void {
                $query->whereNull('last_used_at')->where('created_at', '<', $before)
                    ->orWhere('last_used_at', '<', $before);
            });
            $counts['explanation'] = (clone $query)->count();
            if (! $dryRun) {
                $query->delete();
            }
        }

        if ($type === 'all' || $type === 'tts') {
            $query = TtsCache::where(function ($query) use ($before): void {
                $query->whereNull('last_used_at')->where('created_at', '<', $before)
                    ->orWhere('last_used_at', '<', $before);
            });

            $entries = $query->get();
            $counts['tts'] = $entries->count();

            foreach ($entries as $entry) {
                if (Storage::disk('local')->exists($entry->file_path)) {
                    $counts['tts_files']++;
                    if (! $dryRun) {
                        Storage::disk('local')->delete($entry->file_path);
                    }
                }

                if (! $dryRun) {
                    $entry->delete();
                }
            }
        }

        $this->info(($dryRun ? 'Dry run: ' : '').'AI cache cleanup summary');
        $this->table(['Type', 'Count'], [
            ['translation', $counts['translation']],
            ['explanation', $counts['explanation']],
            ['tts', $counts['tts']],
            ['tts files', $counts['tts_files']],
        ]);

        return self::SUCCESS;
    }
}
