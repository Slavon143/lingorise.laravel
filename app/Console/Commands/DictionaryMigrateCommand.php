<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\User;
use App\Services\Intelligence\Translation\TranslationService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DictionaryMigrateCommand extends Command
{
    protected $signature = 'dictionary:migrate
        {user? : The user ID to migrate words for}
        {--book= : Only migrate words from this book ID}
        {--dry-run : Count words without migrating}
        {--force : Skip confirmation prompt}';

    protected $description = 'Populate dictionary entries from book content using AI translation';

    public function handle(TranslationService $translation): int
    {
        $userId = $this->argument('user');
        $bookId = $this->option('book');

        $query = Book::query();

        if ($userId) {
            $query->where('owner_id', $userId);
        }

        if ($bookId) {
            $query->where('id', $bookId);
        }

        $books = $query->whereNotNull('content')->where('content', '!=', '')->get();

        if ($books->isEmpty()) {
            $this->warn('No books found matching the criteria.');
            return Command::SUCCESS;
        }

        $totalWords = 0;
        $totalMigrated = 0;

        foreach ($books as $book) {
            $words = $this->extractUniqueWords($book->content);
            $totalWords += count($words);

            if ($this->option('dry-run')) {
                $this->line(sprintf('Book "%s" (ID %d): %d unique words', $book->title, $book->id, count($words)));
                continue;
            }

            $owner = $book->owner;
            if (!$owner) {
                $this->warn(sprintf('Book "%s" has no owner, skipping.', $book->title));
                continue;
            }

            $existingEntries = $owner->dictionaryEntries()
                ->where('book_id', $book->id)
                ->pluck('original_text')
                ->map(fn ($t) => mb_strtolower(trim($t)))
                ->toSet();

            foreach ($words as $word) {
                if ($existingEntries->contains(mb_strtolower(trim($word)))) {
                    continue;
                }

                try {
                    $result = $translation->translate(
                        word: $word,
                        context: mb_substr($book->content, 0, 500),
                        sourceLocale: $book->language_locale,
                        nativeLocale: $owner->languagePreference?->native_locale ?? 'en',
                        userId: $owner->id,
                        usageContext: new AiUsageContext(userId: $owner->id),
                    );

                    $owner->dictionaryEntries()->create([
                        'book_id' => $book->id,
                        'original_text' => $word,
                        'translated_text' => $result['translation'] ?? "[unknown: $word]",
                        'status' => 'new',
                    ]);

                    $totalMigrated++;
                    $this->line(sprintf('  ✓ %s → %s', $word, $result['translation'] ?? '?'));
                } catch (\Throwable $e) {
                    $this->warn(sprintf('  ✗ %s: %s', $word, $e->getMessage()));
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf("\nDry run complete. %d total unique words across %d books.", $totalWords, $books->count()));
        } else {
            $this->info(sprintf("\nMigrated %d / %d words across %d books.", $totalMigrated, $totalWords, $books->count()));
        }

        return Command::SUCCESS;
    }

    private function extractUniqueWords(string $content): array
    {
        $cleaned = strip_tags($content);
        $words = preg_split('/\PL+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 2 && mb_strlen($w) <= 50);
        $words = array_map(fn ($w) => mb_strtolower(trim($w)), $words);
        $words = array_unique($words);
        sort($words);
        return array_values($words);
    }
}
