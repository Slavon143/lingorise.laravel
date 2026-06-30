<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SpeakingRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_speech_endpoint_returns_429_after_request_rate_limit_is_exceeded(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $user = User::factory()->withPremiumSubscription()->create();
        $rateLimit = 10;
        $rateLimitKey = md5('ai-speech'.$user->id);
        RateLimiter::clear($rateLimitKey);

        for ($i = 0; $i < $rateLimit; $i++) {
            $this->actingAs($user)
                ->post(route('speech.create'), ['text' => "Request $i", 'locale' => 'en'])
                ->assertOk();
        }

        $this->actingAs($user)
            ->post(route('speech.create'), ['text' => 'Excess request', 'locale' => 'en'])
            ->assertStatus(429);

        Http::assertSentCount($rateLimit);
    }
}
