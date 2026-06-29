<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_regular_user_gets_forbidden_for_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Recent users');
    }

    public function test_admin_can_see_users_list(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'learner@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'visible@example.com']);
        User::factory()->create(['email' => 'hidden@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'visible@example.com']))
            ->assertOk()
            ->assertSee('visible@example.com')
            ->assertDontSee('hidden@example.com');
    }

    public function test_users_list_is_paginated(): void
    {
        $admin = $this->admin();
        User::factory()->count(15)->sequence(
            fn ($sequence) => ['email' => 'user'.$sequence->index.'@example.com'],
        )->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['sort' => 'id', 'direction' => 'desc']))
            ->assertOk()
            ->assertSee('user14@example.com')
            ->assertDontSee('user0@example.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['sort' => 'id', 'direction' => 'desc', 'page' => 2]))
            ->assertOk()
            ->assertSee('user0@example.com');
    }

    public function test_admin_can_update_user_name(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'New Name',
                'email' => 'NEW@example.com',
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_promote_another_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->post(route('admin.users.promote', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_regular_user_cannot_promote_admin(): void
    {
        $regular = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create(['is_admin' => false]);

        $this->actingAs($regular)
            ->post(route('admin.users.promote', $target))
            ->assertForbidden();

        $this->assertFalse($target->fresh()->isAdmin());
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.demote', $admin))
            ->assertSessionHasErrors('admin');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_is_admin_cannot_be_changed_through_public_language_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->put(route('settings.languages'), [
                'native_locale' => 'de',
                'learning_locale' => 'en',
                'is_admin' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_user_update_creates_audit_log(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Before', 'email' => 'before@example.com']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'After',
                'email' => 'after@example.com',
            ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.updated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
        ]);

        $this->assertSame('Before', AdminAuditLog::first()->old_values['name']);
    }

    public function test_settings_page_does_not_reveal_secrets(): void
    {
        config([
            'services.openai.key' => 'sk-super-secret-openai',
            'services.stripe.secret' => 'sk-super-secret-stripe',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('OpenAI configured')
            ->assertSee('yes')
            ->assertDontSee('sk-super-secret-openai')
            ->assertDontSee('sk-super-secret-stripe');
    }

    public function test_books_placeholder_is_available_to_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('Books module')
            ->assertSee('This module will be implemented in the next stage.');
    }

    public function test_ai_placeholder_is_available_to_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('AI &amp; TTS', false)
            ->assertSee('Usage')
            ->assertSee('This module will be implemented in the next stage.');
    }

    public function test_mutating_admin_actions_do_not_work_with_get(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/promote')
            ->assertStatus(405);

        $this->actingAs($admin)
            ->get('/admin/users/'.$admin->id.'/demote')
            ->assertStatus(405);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }
}
