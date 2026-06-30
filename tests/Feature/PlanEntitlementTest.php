<?php

namespace Tests\Feature;

use App\Enums\PlanCode;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\PlanDefaults;
use App\Services\Plans\ReaderEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommended_defaults_match_free_premium_and_pro(): void
    {
        $this->assertSame(10, PlanDefaults::free()['translation_max_words']);
        $this->assertSame(200, PlanDefaults::free()['ai_actions_monthly_limit']);
        $this->assertFalse(PlanDefaults::free()['ai_tts_enabled']);
        $this->assertSame(100, PlanDefaults::free()['vocabulary_entries_limit']);

        $this->assertSame(30, PlanDefaults::premium()['translation_max_words']);
        $this->assertSame(2500, PlanDefaults::premium()['ai_actions_monthly_limit']);
        $this->assertSame(50000, PlanDefaults::premium()['ai_tts_monthly_characters']);
        $this->assertFalse(PlanDefaults::premium()['voice_selection_enabled']);

        $this->assertSame(50, PlanDefaults::pro()['translation_max_words']);
        $this->assertSame(8000, PlanDefaults::pro()['ai_actions_monthly_limit']);
        $this->assertNull(PlanDefaults::pro()['vocabulary_entries_limit']);
        $this->assertTrue(PlanDefaults::pro()['voice_selection_enabled']);
    }

    public function test_word_limits_are_enforced_from_plan_reader_settings(): void
    {
        $service = app(ReaderEntitlementService::class);
        $user = User::factory()->create();

        $this->assertTrue($service->validateWordLimit($user, 'translation', 'one two three four five six seven eight nine ten')['allowed']);
        $this->assertFalse($service->validateWordLimit($user, 'translation', 'one two three four five six seven eight nine ten eleven')['allowed']);
        $this->assertTrue($service->validateWordLimit($user, 'context', 'There is little or no magic')['allowed']);
        $this->assertFalse($service->validateWordLimit($user, 'context', 'There is little or no magic here')['allowed']);
    }

    public function test_pricing_page_hides_admin_and_displays_database_matrix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricing.index'))
            ->assertOk()
            ->assertSee('Free')
            ->assertSee('Premium')
            ->assertSee('Pro')
            ->assertDontSee('Admin')
            ->assertSee('Translation max words')
            ->assertSee('10 words')
            ->assertSee('30 words')
            ->assertSee('50 words');
    }

    public function test_admin_can_reset_plan_defaults_without_changing_price(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::where('code', PlanCode::Premium->value)->firstOrFail();
        $plan->update(['price_amount' => 1234]);
        $plan->readerSettings()->update(['translation_max_words' => 99]);

        $this->actingAs($admin)
            ->post(route('admin.plans.reset-defaults', $plan))
            ->assertRedirect();

        $this->assertSame('1234.00', $plan->fresh()->price_amount);
        $this->assertSame(30, $plan->fresh('readerSettings')->readerSettings->translation_max_words);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'plan.reader_settings.reset_defaults',
            'entity_id' => $plan->id,
        ]);
    }
}
