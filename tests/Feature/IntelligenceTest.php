<?php

namespace Tests\Feature;

use App\Models\AiStructuredCache;
use App\Models\Book;
use App\Models\ShadowingAttempt;
use App\Models\User;
use App\Services\Intelligence\Explanation\ContextExplanationService;
use App\Services\Intelligence\Explanation\GrammarExplanationService;
use App\Services\Intelligence\Explanation\SimplificationService;
use App\Services\Intelligence\Usage\AiUsageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->languagePreference()->create([
            'native_locale' => 'de',
            'learning_locale' => 'en',
        ]);
        $this->book = Book::factory()->for($this->user, 'owner')->create(['language_locale' => 'en']);
    }

    protected function fakeChoice(array $data): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($data)],
                ]],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 100],
            ]),
        ]);
    }

    public function test_translation_returns_null_explanation(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [[
                    'content' => [[
                        'text' => json_encode([
                            'translation' => 'wunderbar',
                            'pronunciation' => '/ˈwʌndəfəl/',
                            'explanation' => null,
                        ]),
                    ]],
                ]],
            ]),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reader.translate', $this->book), [
                'word' => 'wonderful',
                'context' => 'A wonderful day.',
            ])
            ->assertOk()
            ->assertJson([
                'translation' => 'wunderbar',
                'pronunciation' => '/ˈwʌndəfəl/',
                'explanation' => null,
            ]);
    }

    public function test_context_explanation_service(): void
    {
        $this->fakeChoice([
            'meaning_in_context' => 'Something that causes great joy.',
            'base_form' => 'wonderful',
            'part_of_speech' => 'adjective',
            'translation' => 'wunderbar',
            'simple_explanation' => 'Used to describe something very good.',
            'example' => 'It was a wonderful experience.',
            'cefr_level' => 'A2',
            'grammar_form' => 'positive',
            'fixed_expression' => null,
        ]);

        $service = $this->app->make(ContextExplanationService::class);
        $result = $service->explain(
            selectedText: 'wonderful',
            context: 'The garden was full of wonderful secrets.',
            sourceLanguage: 'en',
            targetLanguage: 'de',
            userId: $this->user->id,
            book: $this->book,
            usageContext: new AiUsageContext(userId: $this->user->id),
        );

        $this->assertEquals('wunderbar', $result['data']['translation']);
        $this->assertFalse($result['meta']['cache_hit']);
    }

    public function test_context_explanation_endpoint(): void
    {
        $this->fakeChoice([
            'meaning_in_context' => 'Something that causes great joy.',
            'base_form' => 'wonderful',
            'part_of_speech' => 'adjective',
            'translation' => 'wunderbar',
            'simple_explanation' => 'Used to describe something very good.',
            'example' => 'It was a wonderful experience.',
            'cefr_level' => 'A2',
            'grammar_form' => 'positive',
            'fixed_expression' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reader.context-explain', $this->book), [
                'selected_text' => 'wonderful',
                'context' => 'The garden was full of wonderful secrets.',
                'source_language' => 'en',
                'target_language' => 'de',
            ])
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertEquals('wunderbar', $response->json('data.translation'));
        $this->assertNotNull($response->json('meta.cache_key'));
    }

    public function test_grammar_explanation_service(): void
    {
        $this->fakeChoice([
            'construction' => 'Present Perfect',
            'purpose' => 'Used for past actions with present relevance.',
            'structure' => 'have + past participle',
            'parts' => ['have', 'past participle'],
            'simplified_translation' => 'I have visited London.',
            'additional_example' => 'She has eaten.',
            'common_mistake' => 'Using past simple instead.',
        ]);

        $service = $this->app->make(GrammarExplanationService::class);
        $result = $service->explain(
            text: 'I have visited London.',
            context: null,
            sourceLanguage: 'en',
            targetLanguage: 'de',
            userId: $this->user->id,
            book: $this->book,
            usageContext: new AiUsageContext(userId: $this->user->id),
        );

        $this->assertEquals('Present Perfect', $result['data']['construction']);
        $this->assertFalse($result['meta']['cache_hit']);
    }

    public function test_grammar_explanation_endpoint(): void
    {
        $this->fakeChoice([
            'construction' => 'Present Perfect',
            'purpose' => 'Used for past actions with present relevance.',
            'structure' => 'have + past participle',
            'parts' => ['have', 'past participle'],
            'simplified_translation' => 'I have visited London.',
            'additional_example' => 'She has eaten.',
            'common_mistake' => 'Using past simple instead.',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reader.grammar-explain', $this->book), [
                'text' => 'I have visited London.',
                'source_language' => 'en',
                'target_language' => 'de',
            ])
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Present Perfect', $response->json('data.construction'));
    }

    public function test_simplification_service(): void
    {
        $this->fakeChoice([
            'original' => 'The feline positioned itself upon the mat.',
            'simplified' => 'The cat sat on the mat.',
            'target_level' => 'A2',
            'replacements' => [['original' => 'feline', 'simplified' => 'cat']],
            'changes_explanation' => 'Replaced complex vocabulary.',
        ]);

        $service = $this->app->make(SimplificationService::class);
        $result = $service->simplify(
            text: 'The feline positioned itself upon the mat.',
            sourceLanguage: 'en',
            targetLevel: 'A2',
            userId: $this->user->id,
            book: $this->book,
            usageContext: new AiUsageContext(userId: $this->user->id),
        );

        $this->assertEquals('The cat sat on the mat.', $result['data']['simplified']);
        $this->assertFalse($result['meta']['cache_hit']);
    }

    public function test_simplification_endpoint(): void
    {
        $this->fakeChoice([
            'original' => 'The feline positioned itself upon the mat.',
            'simplified' => 'The cat sat on the mat.',
            'target_level' => 'A2',
            'replacements' => [['original' => 'feline', 'simplified' => 'cat']],
            'changes_explanation' => 'Replaced complex vocabulary.',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reader.simplify', $this->book), [
                'text' => 'The feline positioned itself upon the mat.',
                'source_language' => 'en',
                'target_level' => 'A2',
            ])
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertEquals('The cat sat on the mat.', $response->json('data.simplified'));
    }

    public function test_shadowing_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('reader.shadowing', $this->book), [
                'page_number' => 1,
                'word_index_start' => 0,
                'word_index_end' => 5,
                'sentence_hash' => 'abc123',
                'self_rating' => 'okay',
            ])
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertEquals(1, $response->json('data.attempts_count'));
        $this->assertDatabaseHas('shadowing_attempts', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'sentence_hash' => 'abc123',
            'self_rating' => 'okay',
            'attempts_count' => 1,
        ]);
    }

    public function test_shadowing_increments_attempts(): void
    {
        ShadowingAttempt::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'page_number' => 1,
            'word_index_start' => 0,
            'word_index_end' => 5,
            'sentence_hash' => 'abc123',
            'attempts_count' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reader.shadowing', $this->book), [
                'page_number' => 1,
                'word_index_start' => 0,
                'word_index_end' => 5,
                'sentence_hash' => 'abc123',
            ])
            ->assertOk();

        $this->assertEquals(2, $response->json('data.attempts_count'));
    }

    public function test_structured_cache_is_reused(): void
    {
        $this->fakeChoice([
            'meaning_in_context' => 'Something great.',
            'base_form' => 'great',
            'part_of_speech' => 'adjective',
            'translation' => 'großartig',
            'simple_explanation' => 'Very good.',
            'cefr_level' => 'A1',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reader.context-explain', $this->book), [
                'selected_text' => 'great',
                'context' => 'That is great news.',
                'source_language' => 'en',
            ])
            ->assertOk();

        $this->assertEquals(1, AiStructuredCache::count());

        $this->actingAs($this->user)
            ->postJson(route('reader.context-explain', $this->book), [
                'selected_text' => 'great',
                'context' => 'That is great news.',
                'source_language' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('meta.cache_hit', true);
    }

    public function test_validation_returns_json(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('reader.context-explain', $this->book), [
                'context' => 'Test',
                'source_language' => 'en',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['selected_text']);

        $this->actingAs($this->user)
            ->postJson(route('reader.simplify', $this->book), [
                'text' => 'Test.',
                'source_language' => 'en',
                'target_level' => 'C3',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target_level']);

        $this->actingAs($this->user)
            ->postJson(route('reader.shadowing', $this->book), [
                'page_number' => 1,
                'word_index_start' => 0,
                'word_index_end' => 5,
                'sentence_hash' => 'abc123',
                'self_rating' => 'invalid',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['self_rating']);
    }
}
