<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_read_a_book_and_progress_is_saved(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create([
            'content' => implode(' ', array_fill(0, 800, 'language')),
            'total_words' => 800,
        ]);

        $this->actingAs($user)
            ->get(route('reader.show', ['book' => $book, 'page' => 2]))
            ->assertOk()
            ->assertSee('Page 2');

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'current_page' => 2,
            'words_read' => 700,
        ]);
    }

    public function test_user_cannot_read_someone_elses_private_book(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->for($owner, 'owner')->create(['visibility' => 'private']);

        $this->actingAs($reader)
            ->get(route('reader.show', $book))
            ->assertForbidden();
    }

    public function test_user_can_save_a_word_with_context(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->postJson(route('vocabulary.store', $book), [
                'original_text' => 'wonderful',
                'translated_text' => 'wunderbar',
                'context' => 'The garden was full of wonderful secrets.',
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('dictionary_entries', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'original_text' => 'wonderful',
            'translated_text' => 'wunderbar',
        ]);
    }
}
