<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_pasted_text_to_their_library(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/library', [
                'title' => 'A Small Story',
                'author' => 'Anna Writer',
                'language_locale' => 'en',
                'level' => 'A2',
                'visibility' => 'private',
                'content' => 'This is a small story with several useful English words.',
            ])
            ->assertRedirect('/library');

        $this->assertDatabaseHas('books', [
            'owner_id' => $user->id,
            'title' => 'A Small Story',
            'source_type' => 'text',
        ]);
    }

    public function test_user_can_upload_a_txt_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('story.txt', 'A readable story from a text file.');

        $this->actingAs($user)
            ->post('/library', [
                'title' => 'Uploaded Story',
                'language_locale' => 'en',
                'level' => 'A1',
                'visibility' => 'private',
                'book_file' => $file,
            ])
            ->assertRedirect('/library');

        $this->assertDatabaseHas('books', [
            'owner_id' => $user->id,
            'title' => 'Uploaded Story',
            'source_type' => 'txt',
        ]);
    }

    public function test_user_cannot_delete_someone_elses_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->delete(route('library.destroy', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_user_can_upload_a_custom_book_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/library', [
                'title' => 'Covered Story',
                'language_locale' => 'en',
                'level' => 'A2',
                'visibility' => 'private',
                'content' => 'A short story with a custom cover image.',
                'cover_file' => UploadedFile::fake()->create('cover.jpg', 50, 'image/jpeg'),
            ])
            ->assertRedirect('/library');

        $book = Book::query()->where('title', 'Covered Story')->firstOrFail();

        $this->assertNotNull($book->cover_path);
        Storage::disk('public')->assertExists($book->cover_path);
    }
}
