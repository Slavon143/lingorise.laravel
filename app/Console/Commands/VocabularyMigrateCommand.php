<?php

namespace App\Console\Commands;

use App\Models\DictionaryEntry;
use App\Models\User;
use App\Services\Vocabulary\VocabularyService;
use Illuminate\Console\Command;

class VocabularyMigrateCommand extends Command
{
    protected $signature = 'vocabulary:migrate-dictionary
        {--dry-run : Count entries without migrating}
        {--user= : Only migrate entries for this user ID}
        {--force : Skip confirmation prompt}';

    protected $description = 'Migrate legacy dictionary_entries to user_words + user_word_events';

    public function handle(VocabularyService $vocabulary): int
    {
        $query = DictionaryEntry::with(['user', 'book']);

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No dictionary entries found to migrate.');
            return Command::SUCCESS;
        }

        $this->line(sprintf("Found <fg=yellow>%s</> dictionary entries to process.", number_format($total)));

        if ($this->option('dry-run')) {
            $this->info(sprintf("Dry run: %s entries would be migrated.", number_format($total)));
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Migrate {$total} entries to user_words?", true)) {
            $this->warn('Cancelled.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        $query->chunk(100, function ($entries) use ($vocabulary, &$migrated, &$skipped, &$errors, $bar): void {
            foreach ($entries as $entry) {
                try {
                    $user = $entry->user;

                    if (!$user) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    $word = $vocabulary->migrateFromDictionary($user, $entry);

                    if ($word->wasRecentlyCreated) {
                        $migrated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->warn(sprintf('Entry #%d: %s', $entry->id, $e->getMessage()));
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Migrated: %s | Skipped: %s | Errors: %s', $migrated, $skipped, $errors));

        return Command::SUCCESS;
    }
}
