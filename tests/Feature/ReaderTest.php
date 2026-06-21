<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'words_read' => 540,
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
            ->assertJson([
                'saved' => true,
                'entry' => [
                    'original_text' => 'wonderful',
                    'translated_text' => 'wunderbar',
                ],
            ]);

        $this->assertDatabaseHas('dictionary_entries', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'original_text' => 'wonderful',
            'translated_text' => 'wunderbar',
        ]);
    }

    public function test_reader_shows_saved_vocabulary_for_the_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create([
            'content' => 'The garden was full of wonderful secrets.',
        ]);
        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'wonderful',
            'translated_text' => 'wunderbar',
            'context' => 'The garden was full of wonderful secrets.',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('reader.show', $book))
            ->assertOk()
            ->assertSee('wonderful')
            ->assertSee('wunderbar');
    }

    public function test_user_can_open_search_and_delete_vocabulary(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create(['title' => 'Secret Garden']);
        $entry = $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'wonderful secrets',
            'translated_text' => 'wunderbare Geheimnisse',
            'context' => 'The garden was full of wonderful secrets.',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('vocabulary.index', ['q' => 'Geheimnisse']))
            ->assertOk()
            ->assertSee('wonderful secrets')
            ->assertSee('Secret Garden')
            ->assertSee('Phrase')
            ->assertSee('Translation')
            ->assertSee('From the book')
            ->assertSee('Open on page 1');

        $this->actingAs($user)
            ->delete(route('vocabulary.destroy', $entry))
            ->assertRedirect();

        $this->assertDatabaseMissing('dictionary_entries', ['id' => $entry->id]);
    }

    public function test_vocabulary_link_opens_the_page_containing_the_phrase(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create([
            'content' => implode(' ', array_fill(0, 350, 'before'))."\n\nThe hidden garden was full of wonderful secrets.",
        ]);
        $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'wonderful secrets',
            'translated_text' => 'wunderbare Geheimnisse',
            'context' => 'The hidden garden was full of wonderful secrets.',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('vocabulary.index'))
            ->assertOk()
            ->assertSee('Open on page 2')
            ->assertSee('focus=wonderful%20secrets', false);
    }

    public function test_user_cannot_delete_another_users_vocabulary(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $entry = $owner->dictionaryEntries()->create([
            'original_text' => 'private',
            'translated_text' => 'privat',
            'status' => 'new',
        ]);

        $this->actingAs($otherUser)
            ->delete(route('vocabulary.destroy', $entry))
            ->assertForbidden();
    }

    public function test_reader_can_automatically_translate_a_word(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [[
                    'content' => [[
                        'text' => json_encode([
                            'translation' => 'wunderbar',
                            'pronunciation' => '/ˈwʌndəfəl/',
                            'explanation' => 'Etwas, das große Freude oder Bewunderung hervorruft.',
                        ]),
                    ]],
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $user->languagePreference()->create([
            'native_locale' => 'de',
            'learning_locale' => 'en',
        ]);
        $book = Book::factory()->for($user, 'owner')->create(['language_locale' => 'en']);

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), [
                'word' => 'wonderful',
                'context' => 'The garden was full of wonderful secrets.',
            ])
            ->assertOk()
            ->assertJson([
                'translation' => 'wunderbar',
                'pronunciation' => '/ˈwʌndəfəl/',
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['model'] === 'gpt-5.4-mini'
            && $request['store'] === false);
    }

    public function test_translation_falls_back_cleanly_when_openai_is_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), [
                'word' => 'wonderful',
                'context' => 'The garden was full of wonderful secrets.',
            ])
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Automatic translation is not configured.']);
    }

    public function test_translation_is_limited_to_ten_words(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), [
                'word' => 'one two three four five six seven eight nine ten eleven',
                'context' => 'A deliberately long selected phrase.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.word.0', 'You can translate up to 10 words at once.');
    }
}
