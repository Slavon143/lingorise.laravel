<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\TtsCache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_translation_and_explanation_are_cached(): void
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

        $payload = [
            'word' => 'wonderful',
            'context' => 'The garden was full of wonderful secrets.',
        ];

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), $payload)
            ->assertOk()
            ->assertJson([
                'translation' => 'wunderbar',
                'pronunciation' => '/ˈwʌndəfəl/',
                'explanation' => 'Etwas, das große Freude oder Bewunderung hervorruft.',
            ]);

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), $payload)
            ->assertOk()
            ->assertJson([
                'translation' => 'wunderbar',
                'pronunciation' => '/ˈwʌndəfəl/',
            ]);

        Http::assertSentCount(2);

        $this->assertDatabaseHas('translation_cache', [
            'source_text' => 'wonderful',
            'source_language' => 'en',
            'target_language' => 'de',
            'translated_text' => 'wunderbar',
            'hits' => 1,
        ]);

        $this->assertDatabaseHas('explanation_cache', [
            'selected_text' => 'wonderful',
            'context_text' => 'The garden was full of wonderful secrets.',
            'source_language' => 'en',
            'target_language' => 'de',
            'hits' => 1,
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'type' => 'translation',
            'cache_hit' => true,
            'status' => 'success',
        ]);
    }

    public function test_translation_cache_is_context_sensitive_for_explanations(): void
    {
        config(['services.openai.key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'output' => [[
                        'content' => [[
                            'text' => json_encode([
                                'translation' => 'Bank',
                                'pronunciation' => '/bæŋk/',
                                'explanation' => 'Ein Finanzinstitut.',
                            ]),
                        ]],
                    ]],
                ])
                ->push([
                    'output' => [[
                        'content' => [[
                            'text' => json_encode([
                                'explanation' => 'Ein Finanzinstitut.',
                            ]),
                        ]],
                    ]],
                ])
                ->push([
                    'output' => [[
                        'content' => [[
                            'text' => json_encode([
                                'explanation' => 'Der Rand eines Flusses.',
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
                'word' => 'bank',
                'context' => 'She opened an account at the bank.',
            ])
            ->assertOk()
            ->assertJsonPath('explanation', 'Ein Finanzinstitut.');

        $this->actingAs($user)
            ->postJson(route('reader.translate', $book), [
                'word' => 'bank',
                'context' => 'They sat on the river bank.',
            ])
            ->assertOk()
            ->assertJsonPath('explanation', 'Der Rand eines Flusses.');

        Http::assertSentCount(3);
        $this->assertDatabaseCount('explanation_cache', 2);
    }

    public function test_tts_audio_is_cached_on_disk(): void
    {
        config(['services.openai.key' => 'test-key']);
        Storage::fake('local');
        Http::fake([
            'api.openai.com/*' => Http::response('audio-one', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Hello there', 'locale' => 'en'])
            ->assertOk()
            ->assertHeader('X-AI-Cache', 'MISS')
            ->assertContent('audio-one');

        $entry = TtsCache::firstOrFail();
        Storage::disk('local')->assertExists($entry->file_path);

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Hello there', 'locale' => 'en'])
            ->assertOk()
            ->assertHeader('X-AI-Cache', 'HIT')
            ->assertContent('audio-one');

        Http::assertSentCount(1);

        $this->assertDatabaseHas('tts_cache', [
            'source_text' => 'Hello there',
            'language' => 'en',
            'hits' => 1,
        ]);
    }

    public function test_tts_cache_regenerates_when_audio_file_is_missing(): void
    {
        config(['services.openai.key' => 'test-key']);
        Storage::fake('local');
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('audio-one', 200, ['Content-Type' => 'audio/mpeg'])
                ->push('audio-two', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Listen again', 'locale' => 'en'])
            ->assertOk()
            ->assertHeader('X-AI-Cache', 'MISS')
            ->assertContent('audio-one');

        $entry = TtsCache::firstOrFail();
        Storage::disk('local')->delete($entry->file_path);

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Listen again', 'locale' => 'en'])
            ->assertOk()
            ->assertHeader('X-AI-Cache', 'MISS')
            ->assertContent('audio-two');

        Http::assertSentCount(2);
        $this->assertDatabaseCount('tts_cache', 1);
    }

    public function test_empty_tts_response_is_not_cached(): void
    {
        config(['services.openai.key' => 'test-key']);
        Storage::fake('local');
        Http::fake([
            'api.openai.com/*' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Silent', 'locale' => 'en'])
            ->assertStatus(503);

        $this->assertDatabaseCount('tts_cache', 1);
        $this->assertDatabaseHas('tts_cache', ['status' => 'failed']);
        Storage::disk('local')->assertMissing('private/tts');
    }
}
