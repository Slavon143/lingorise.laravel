<?php

use App\Enums\PlanCode;
use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->decimal('price_amount', 10, 2)->nullable();
            $table->string('price_currency', 3)->nullable();
            $table->string('billing_interval', 16)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_ai_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('translations_per_day')->nullable();
            $table->unsignedInteger('translations_per_month')->nullable();
            $table->unsignedInteger('explanations_per_day')->nullable();
            $table->unsignedInteger('explanations_per_month')->nullable();
            $table->unsignedInteger('tts_minutes_per_day')->nullable();
            $table->unsignedInteger('tts_minutes_per_month')->nullable();
            $table->unsignedSmallInteger('max_translation_characters')->default(500);
            $table->unsignedSmallInteger('max_explanation_selected_characters')->default(255);
            $table->unsignedSmallInteger('max_explanation_context_characters')->default(1000);
            $table->unsignedSmallInteger('max_tts_characters_per_request')->default(500);
            $table->unsignedSmallInteger('requests_per_minute')->default(10);
            $table->unsignedTinyInteger('concurrent_tts_requests')->default(1);
            $table->boolean('ai_translation_enabled')->default(true);
            $table->boolean('ai_explanation_enabled')->default(true);
            $table->boolean('ai_tts_enabled')->default(false);
            $table->boolean('browser_tts_enabled')->default(true);
            $table->boolean('premium_books_enabled')->default(false);
            $table->unsignedSmallInteger('private_books_limit')->nullable();
            $table->timestamps();
        });

        Schema::create('user_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status', 24)->default(SubscriptionStatus::Active->value);
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('source', 24)->default(SubscriptionSource::System->value);
            $table->string('external_provider', 32)->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('user_ai_limit_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('translations_per_day')->nullable();
            $table->unsignedInteger('translations_per_month')->nullable();
            $table->unsignedInteger('explanations_per_day')->nullable();
            $table->unsignedInteger('explanations_per_month')->nullable();
            $table->unsignedInteger('tts_minutes_per_day')->nullable();
            $table->unsignedInteger('tts_minutes_per_month')->nullable();
            $table->unsignedSmallInteger('max_translation_characters')->nullable();
            $table->unsignedSmallInteger('max_explanation_context_characters')->nullable();
            $table->unsignedSmallInteger('max_tts_characters_per_request')->nullable();
            $table->boolean('ai_translation_enabled')->nullable();
            $table->boolean('ai_explanation_enabled')->nullable();
            $table->boolean('ai_tts_enabled')->nullable();
            $table->boolean('browser_tts_enabled')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        $this->migrateExistingPlans();
    }

    private function migrateExistingPlans(): void
    {
        $freePlanId = DB::table('plans')->insertGetId([
            'code' => PlanCode::Free->value,
            'name' => 'Free',
            'description' => 'Perfect for getting started with reading and vocabulary.',
            'is_active' => true,
            'is_default' => true,
            'price_amount' => 0,
            'price_currency' => 'USD',
            'billing_interval' => 'month',
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $premiumPlanId = DB::table('plans')->insertGetId([
            'code' => PlanCode::Premium->value,
            'name' => 'Premium',
            'description' => 'For committed learners who want the full experience.',
            'is_active' => true,
            'is_default' => false,
            'price_amount' => 9,
            'price_currency' => 'USD',
            'billing_interval' => 'month',
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminPlanId = DB::table('plans')->insertGetId([
            'code' => PlanCode::Admin->value,
            'name' => 'Admin',
            'description' => 'Unlimited access for administrators.',
            'is_active' => true,
            'is_default' => false,
            'price_amount' => null,
            'price_currency' => null,
            'billing_interval' => null,
            'position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('plan_ai_limits')->insert([
            // Free
            [
                'plan_id' => $freePlanId,
                'translations_per_day' => 20,
                'translations_per_month' => null,
                'explanations_per_day' => 5,
                'explanations_per_month' => null,
                'tts_minutes_per_day' => null,
                'tts_minutes_per_month' => 0,
                'max_translation_characters' => 500,
                'max_explanation_selected_characters' => 255,
                'max_explanation_context_characters' => 1000,
                'max_tts_characters_per_request' => 0,
                'requests_per_minute' => 10,
                'concurrent_tts_requests' => 0,
                'ai_translation_enabled' => true,
                'ai_explanation_enabled' => true,
                'ai_tts_enabled' => false,
                'browser_tts_enabled' => true,
                'premium_books_enabled' => false,
                'private_books_limit' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Premium
            [
                'plan_id' => $premiumPlanId,
                'translations_per_day' => 300,
                'translations_per_month' => null,
                'explanations_per_day' => 100,
                'explanations_per_month' => null,
                'tts_minutes_per_day' => null,
                'tts_minutes_per_month' => 600,
                'max_translation_characters' => 5000,
                'max_explanation_selected_characters' => 255,
                'max_explanation_context_characters' => 3000,
                'max_tts_characters_per_request' => 500,
                'requests_per_minute' => 30,
                'concurrent_tts_requests' => 2,
                'ai_translation_enabled' => true,
                'ai_explanation_enabled' => true,
                'ai_tts_enabled' => true,
                'browser_tts_enabled' => true,
                'premium_books_enabled' => true,
                'private_books_limit' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Admin
            [
                'plan_id' => $adminPlanId,
                'translations_per_day' => null,
                'translations_per_month' => null,
                'explanations_per_day' => null,
                'explanations_per_month' => null,
                'tts_minutes_per_day' => null,
                'tts_minutes_per_month' => null,
                'max_translation_characters' => 5000,
                'max_explanation_selected_characters' => 255,
                'max_explanation_context_characters' => 3000,
                'max_tts_characters_per_request' => 500,
                'requests_per_minute' => 60,
                'concurrent_tts_requests' => 5,
                'ai_translation_enabled' => true,
                'ai_explanation_enabled' => true,
                'ai_tts_enabled' => true,
                'browser_tts_enabled' => true,
                'premium_books_enabled' => true,
                'private_books_limit' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $planMap = [
            'free' => $freePlanId,
            'pro' => $premiumPlanId,
        ];

        DB::table('users')->orderBy('id')->each(function ($user) use ($planMap, $freePlanId): void {
            $planId = $planMap[$user->plan ?? 'free'] ?? $freePlanId;

            DB::table('user_subscriptions')->insert([
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => 'active',
                'starts_at' => $user->created_at ?? now(),
                'ends_at' => $user->plan === 'pro' ? now()->addYear() : null,
                'source' => SubscriptionSource::System->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_limit_overrides');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('plan_ai_limits');
        Schema::dropIfExists('plans');
    }
};
