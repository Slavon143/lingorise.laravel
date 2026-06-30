<?php

use App\Enums\PlanCode;
use App\Services\Plans\PlanDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->updateOrInsert(
            ['code' => PlanCode::Pro->value],
            [
                'name' => 'Pro',
                'description' => 'For intensive learning and advanced AI tools.',
                'is_active' => true,
                'is_default' => false,
                'price_amount' => 19,
                'price_currency' => 'USD',
                'billing_interval' => 'month',
                'position' => 3,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        if (! Schema::hasTable('plan_reader_settings')) {
            return;
        }

        Schema::table('plan_reader_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_reader_settings', 'ai_actions_monthly_limit')) {
                $table->integer('ai_actions_monthly_limit')->nullable()->after('ai_actions_daily_limit');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'vocabulary_entries_limit')) {
                $table->integer('vocabulary_entries_limit')->nullable()->after('ai_tts_monthly_characters');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'private_books_limit')) {
                $table->integer('private_books_limit')->nullable()->after('vocabulary_entries_limit');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'daily_goal_enabled')) {
                $table->boolean('daily_goal_enabled')->default(true)->after('vocabulary_enabled');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'streak_enabled')) {
                $table->boolean('streak_enabled')->default(true)->after('daily_goal_enabled');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'import_private_books_enabled')) {
                $table->boolean('import_private_books_enabled')->default(true)->after('streak_enabled');
            }
            if (! Schema::hasColumn('plan_reader_settings', 'public_library_enabled')) {
                $table->boolean('public_library_enabled')->default(true)->after('import_private_books_enabled');
            }
        });

        foreach ([PlanCode::Free->value, PlanCode::Premium->value, PlanCode::Pro->value] as $code) {
            $plan = DB::table('plans')->where('code', $code)->first();
            if (! $plan) {
                continue;
            }

            DB::table('plan_reader_settings')->updateOrInsert(
                ['plan_id' => $plan->id],
                array_merge(PlanDefaults::for($code), ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan_reader_settings')) {
            return;
        }

        Schema::table('plan_reader_settings', function (Blueprint $table) {
            $columns = [
                'ai_actions_monthly_limit',
                'vocabulary_entries_limit',
                'private_books_limit',
                'daily_goal_enabled',
                'streak_enabled',
                'import_private_books_enabled',
                'public_library_enabled',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('plan_reader_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
