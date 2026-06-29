<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpeakingTest extends TestCase
{
    use RefreshDatabase;

    public function test_speaking_page_uses_saved_vocabulary(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user, 'owner')->create([
            'title' => 'The Secret Garden',
            'language_locale' => 'en',
        ]);
        $entry = $user->dictionaryEntries()->create([
            'book_id' => $book->id,
            'original_text' => 'wonderful secrets',
            'translated_text' => 'wunderbare Geheimnisse',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('speaking.index', ['entry' => $entry->id]))
            ->assertOk()
            ->assertSee('Speak it out loud.')
            ->assertSee('wonderful secrets')
            ->assertSee('wunderbare Geheimnisse')
            ->assertSee('The Secret Garden');
    }

    public function test_speaking_page_has_an_empty_state_without_vocabulary(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('speaking.index'))
            ->assertOk()
            ->assertSee('Save a phrase before you practise it.');
    }

    public function test_user_cannot_select_another_users_speaking_entry(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $entry = $owner->dictionaryEntries()->create([
            'original_text' => 'private phrase',
            'translated_text' => 'private Übersetzung',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('speaking.index', ['entry' => $entry->id]))
            ->assertOk()
            ->assertDontSee('private phrase');
    }

    public function test_authenticated_user_can_generate_natural_speech(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.tts_model' => 'gpt-4o-mini-tts',
            'services.openai.tts_voice' => 'marin',
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('speech.create'), [
                'text' => 'wonderful secrets',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg');

        Http::assertSent(fn ($request) => $request['model'] === 'gpt-4o-mini-tts'
            && $request['voice'] === 'marin'
            && $request['input'] === 'wonderful secrets');
    }

    public function test_natural_speech_requires_openai_configuration(): void
    {
        config(['services.openai.key' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('speech.create'), [
                'text' => 'wonderful secrets',
                'locale' => 'en',
            ])
            ->assertStatus(503);
    }

    public function test_free_user_cannot_generate_speech_after_daily_ai_limit_is_reached(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $user = User::factory()->create();
        Cache::put('daily-translations:'.$user->id.':'.now()->toDateString(), 10, now()->endOfDay());

        $this->actingAs($user)
            ->postJson(route('speech.create'), [
                'text' => 'wonderful secrets',
                'locale' => 'en',
            ])
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => 'Daily free limit of 10 AI actions reached. Upgrade to Pro for unlimited practice.',
            ]);

        Http::assertNothingSent();
    }
}
