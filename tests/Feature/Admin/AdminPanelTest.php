<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\Book;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LanguageSeeder::class);
    }

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

    public function test_admin_can_see_books_list(): void
    {
        $admin = $this->admin();
        Book::factory()->create([
            'title' => 'Test Book Title',
            'author' => 'Test Author',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('Test Book Title')
            ->assertSee('Test Author')
            ->assertDontSee('Books module');
    }

    public function test_admin_can_open_book_edit_page(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'title' => 'Editable Book',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.books.edit', $book))
            ->assertOk()
            ->assertSee('Editable Book')
            ->assertSee('Save changes');
    }

    public function test_admin_can_update_book_metadata(): void
    {
        $admin = $this->admin();
        $lang = Language::first();
        $book = Book::factory()->create([
            'title' => 'Original Title',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.books.update', $book), [
                'title' => 'Updated Title',
                'access_type' => 'public',
                'status' => 'published',
                'language_id' => $lang->id,
                'is_featured' => true,
            ])
            ->assertRedirect(route('admin.books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Title',
            'access_type' => 'public',
            'processing_status' => 'published',
            'language_id' => $lang->id,
        ]);
    }

    public function test_book_update_creates_audit_log(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'title' => 'Before',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.books.update', $book), [
                'title' => 'After',
                'access_type' => 'public',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'book.updated',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
        ]);

        $this->assertSame('Before', AdminAuditLog::where('action', 'book.updated')->first()->old_values['title']);
    }

    public function test_regular_user_cannot_access_book_edit(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $book = Book::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.books.edit', $book))
            ->assertForbidden();
    }

    public function test_mutating_book_publish_does_not_work_with_get(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create(['owner_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/admin/books/'.$book->id.'/publish')
            ->assertStatus(405);
    }

    public function test_ai_dashboard_is_available_to_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai.overview'))
            ->assertOk()
            ->assertSee('AI &amp; TTS', false)
            ->assertSee('User operations')
            ->assertSee('Provider calls');
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

    public function test_guest_is_redirected_to_login_for_all_admin_pages(): void
    {
        $this->get(route('admin.books.index'))->assertRedirect(route('login'));
        $this->get(route('admin.books.create'))->assertRedirect(route('login'));
        $this->get(route('admin.authors.index'))->assertRedirect(route('login'));
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        $this->get(route('admin.languages.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_gets_forbidden_for_all_admin_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.books.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_draft_book(): void
    {
        $admin = $this->admin();
        $lang = Language::first();

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'New Draft Book',
                'access_type' => 'public',
                'status' => 'draft',
                'language_id' => $lang->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'New Draft Book',
            'processing_status' => 'draft',
            'access_type' => 'public',
        ]);
    }

    public function test_slug_is_generated_from_title(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'My Unique Book',
                'access_type' => 'public',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('books', [
            'title' => 'My Unique Book',
            'slug' => 'my-unique-book',
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'Invalid Status Book',
                'access_type' => 'public',
                'status' => 'invalid_status',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_invalid_access_type_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'Invalid Access Book',
                'access_type' => 'secret',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('access_type');
    }

    public function test_admin_can_publish_book(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'ready',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.publish', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'processing_status' => 'published',
        ]);

        $this->assertNotNull($book->fresh()->published_at);
    }

    public function test_published_book_gets_published_at(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'ready',
            'published_at' => null,
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.publish', $book));

        $this->assertNotNull($book->fresh()->published_at);
    }

    public function test_admin_can_unpublish_book(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'published',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.unpublish', $book))
            ->assertRedirect();

        $this->assertEquals('ready', $book->fresh()->processing_status);
    }

    public function test_admin_can_archive_book(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'ready',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.archive', $book))
            ->assertRedirect();

        $fresh = $book->fresh();
        $this->assertEquals('archived', $fresh->processing_status);
        $this->assertNotNull($fresh->archived_at);
    }

    public function test_admin_can_restore_book(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'archived',
            'archived_at' => now(),
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.restore', $book))
            ->assertRedirect();

        $fresh = $book->fresh();
        $this->assertEquals('draft', $fresh->processing_status);
        $this->assertNull($fresh->archived_at);
    }

    public function test_book_deletion_performs_soft_delete(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create(['owner_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete(route('admin.books.destroy', $book))
            ->assertRedirect();

        $this->assertSoftDeleted($book);
    }

    public function test_audit_log_created_for_book_publish(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create([
            'processing_status' => 'ready',
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.books.publish', $book));

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'book.published',
            'entity_type' => Book::class,
            'entity_id' => $book->id,
        ]);
    }

    public function test_book_list_is_paginated(): void
    {
        $admin = $this->admin();
        Book::factory()->count(20)->sequence(
            fn ($seq) => ['title' => 'Book '.$seq->index, 'owner_id' => $admin->id],
        )->create();

        $this->actingAs($admin)
            ->get(route('admin.books.index', ['sort' => 'id', 'direction' => 'desc']))
            ->assertOk()
            ->assertSee('Book 19')
            ->assertDontSee('Book 0');

        $this->actingAs($admin)
            ->get(route('admin.books.index', ['sort' => 'id', 'direction' => 'desc', 'page' => 2]))
            ->assertOk()
            ->assertSee('Book 0');
    }

    public function test_admin_can_create_store_and_edit_author(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.authors.store'), [
                'name' => 'Test Author',
            ])
            ->assertRedirect();

        $author = \App\Models\Author::where('name', 'Test Author')->first();
        $this->assertNotNull($author);

        $this->actingAs($admin)
            ->patch(route('admin.authors.update', $author), [
                'name' => 'Updated Author',
                'country' => 'US',
            ])
            ->assertRedirect();

        $this->assertEquals('Updated Author', $author->fresh()->name);
    }

    public function test_cannot_delete_author_with_books(): void
    {
        $admin = $this->admin();
        $author = \App\Models\Author::create(['name' => 'Busy Author', 'slug' => 'busy-author']);
        Book::factory()->create([
            'author_id' => $author->id,
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.authors.destroy', $author))
            ->assertSessionHasErrors('author');
    }

    public function test_cannot_delete_category_with_books(): void
    {
        $admin = $this->admin();
        $category = \App\Models\Category::create(['name' => 'Busy Category', 'slug' => 'busy-category']);
        Book::factory()->create([
            'category_id' => $category->id,
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('category');
    }

    public function test_cannot_delete_language_with_books(): void
    {
        $admin = $this->admin();
        $language = Language::first();
        $book = Book::factory()->create([
            'language_id' => $language->id,
            'owner_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.languages.destroy', $language))
            ->assertSessionHasErrors('language');
    }

    public function test_inactive_language_cannot_be_selected_for_new_book(): void
    {
        $admin = $this->admin();
        $lang = Language::first();
        $lang->update(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'Book with inactive language',
                'access_type' => 'public',
                'status' => 'draft',
                'language_id' => $lang->id,
            ])
            ->assertSessionHasErrors('language_id');
    }

    public function test_cover_upload_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'title' => 'Book with bad cover',
                'access_type' => 'public',
                'status' => 'draft',
                'cover' => 'not-a-file',
            ])
            ->assertSessionHasErrors('cover');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }
}
