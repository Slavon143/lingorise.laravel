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
        if (! Schema::hasTable('plan_reader_settings')) {
            return;
        }

        Schema::table('plan_reader_settings', function (Blueprint $table) {
            $table->integer('ai_actions_monthly_limit')->nullable()->after('ai_actions_daily_limit');
            $table->integer('vocabulary_entries_limit')->nullable()->after('ai_tts_monthly_characters');
            $table->integer('private_books_limit')->nullable()->after('vocabulary_entries_limit');
            $table->boolean('daily_goal_enabled')->default(true)->after('vocabulary_enabled');
            $table->boolean('streak_enabled')->default(true)->after('daily_goal_enabled');
            $table->boolean('import_private_books_enabled')->default(true)->after('streak_enabled');
            $table->boolean('public_library_enabled')->default(true)->after('import_private_books_enabled');
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
        Schema::table('plan_reader_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_actions_monthly_limit',
                'vocabulary_entries_limit',
                'private_books_limit',
                'daily_goal_enabled',
                'streak_enabled',
                'import_private_books_enabled',
                'public_library_enabled',
            ]);
        });
    }
};
