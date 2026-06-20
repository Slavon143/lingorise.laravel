<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Anna Reader',
            'email' => 'anna@example.com',
            'password' => 'stories123',
            'password_confirmation' => 'stories123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'anna@example.com']);
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'password' => 'stories123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'stories123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_save_language_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/languages', [
                'native_locale' => 'de',
                'learning_locale' => 'en',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('language_preferences', [
            'user_id' => $user->id,
            'native_locale' => 'de',
            'learning_locale' => 'en',
        ]);
    }

    public function test_native_and_learning_languages_must_be_different(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/languages', [
                'native_locale' => 'en',
                'learning_locale' => 'en',
            ])
            ->assertSessionHasErrors(['native_locale', 'learning_locale']);
    }
}
