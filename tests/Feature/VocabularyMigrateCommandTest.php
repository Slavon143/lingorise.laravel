<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Models\UserWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VocabularyMigrateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_migrate(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create(['language_locale' => 'de']);

        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Hallo',
            'translated_text' => 'Hello',
        ]);

        Artisan::call('vocabulary:migrate-dictionary', ['--dry-run' => true]);

        $this->assertStringContainsString('Dry run', Artisan::output());
        $this->assertSame(0, UserWord::count());
    }

    public function test_migrates_entries_to_user_words(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create(['language_locale' => 'de']);

        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Hallo',
            'translated_text' => 'Hello',
        ]);

        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Tschüss',
            'translated_text' => 'Bye',
        ]);

        Artisan::call('vocabulary:migrate-dictionary', ['--force' => true]);

        $this->assertSame(2, UserWord::count());
        $this->assertDatabaseHas('user_words', [
            'user_id' => $user->id,
            'lemma' => 'hallo',
            'display_word' => 'Hallo',
            'translation' => 'Hello',
        ]);
        $this->assertDatabaseHas('user_words', [
            'user_id' => $user->id,
            'lemma' => 'tschüss',
            'display_word' => 'Tschüss',
            'translation' => 'Bye',
        ]);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create(['language_locale' => 'de']);

        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Hallo',
            'translated_text' => 'Hello',
        ]);

        Artisan::call('vocabulary:migrate-dictionary', ['--force' => true]);
        Artisan::call('vocabulary:migrate-dictionary', ['--force' => true]);

        $this->assertSame(1, UserWord::count());
    }

    public function test_handles_mixed_users_and_only_migrates_valid_ones(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $book = Book::factory()->for($alice, 'owner')->create(['language_locale' => 'de']);

        $alice->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Hallo',
            'translated_text' => 'Hello',
        ]);

        $bob->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'Tschüss',
            'translated_text' => 'Bye',
        ]);

        Artisan::call('vocabulary:migrate-dictionary', ['--force' => true]);

        $this->assertSame(2, UserWord::count());
        $this->assertSame(1, UserWord::where('user_id', $alice->id)->count());
        $this->assertSame(1, UserWord::where('user_id', $bob->id)->count());
    }
}
