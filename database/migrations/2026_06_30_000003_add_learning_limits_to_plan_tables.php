<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_ai_limits', function (Blueprint $table) {
            $table->integer('context_explanations_per_day')->nullable()->after('explanations_per_month');
            $table->integer('context_explanations_per_month')->nullable()->after('context_explanations_per_day');
            $table->integer('grammar_explanations_per_day')->nullable()->after('context_explanations_per_month');
            $table->integer('grammar_explanations_per_month')->nullable()->after('grammar_explanations_per_day');
            $table->integer('simplifications_per_day')->nullable()->after('grammar_explanations_per_month');
            $table->integer('simplifications_per_month')->nullable()->after('simplifications_per_day');
            $table->integer('max_context_explanation_characters')->nullable()->after('max_explanation_context_characters');
            $table->integer('max_grammar_explanation_characters')->nullable()->after('max_context_explanation_characters');
            $table->integer('max_simplification_characters')->nullable()->after('max_grammar_explanation_characters');
            $table->boolean('ai_context_explanation_enabled')->nullable()->after('ai_explanation_enabled');
            $table->boolean('ai_grammar_explanation_enabled')->nullable()->after('ai_context_explanation_enabled');
            $table->boolean('ai_simplification_enabled')->nullable()->after('ai_grammar_explanation_enabled');
            $table->boolean('shadowing_enabled')->nullable()->after('browser_tts_enabled');
        });

        Schema::table('user_ai_limit_overrides', function (Blueprint $table) {
            $table->integer('context_explanations_per_day')->nullable()->after('explanations_per_month');
            $table->integer('context_explanations_per_month')->nullable()->after('context_explanations_per_day');
            $table->integer('grammar_explanations_per_day')->nullable()->after('context_explanations_per_month');
            $table->integer('grammar_explanations_per_month')->nullable()->after('grammar_explanations_per_day');
            $table->integer('simplifications_per_day')->nullable()->after('grammar_explanations_per_month');
            $table->integer('simplifications_per_month')->nullable()->after('simplifications_per_day');
            $table->integer('max_context_explanation_characters')->nullable()->after('max_explanation_context_characters');
            $table->integer('max_grammar_explanation_characters')->nullable()->after('max_context_explanation_characters');
            $table->integer('max_simplification_characters')->nullable()->after('max_grammar_explanation_characters');
            $table->boolean('ai_context_explanation_enabled')->nullable()->after('ai_explanation_enabled');
            $table->boolean('ai_grammar_explanation_enabled')->nullable()->after('ai_context_explanation_enabled');
            $table->boolean('ai_simplification_enabled')->nullable()->after('ai_grammar_explanation_enabled');
            $table->boolean('shadowing_enabled')->nullable()->after('browser_tts_enabled');
        });
    }

    public function down(): void
    {
        $planColumns = [
            'context_explanations_per_day', 'context_explanations_per_month',
            'grammar_explanations_per_day', 'grammar_explanations_per_month',
            'simplifications_per_day', 'simplifications_per_month',
            'max_context_explanation_characters', 'max_grammar_explanation_characters',
            'max_simplification_characters',
            'ai_context_explanation_enabled', 'ai_grammar_explanation_enabled',
            'ai_simplification_enabled', 'shadowing_enabled',
        ];

        Schema::table('plan_ai_limits', function (Blueprint $table) use ($planColumns) {
            foreach ($planColumns as $column) {
                $table->dropColumn($column);
            }
        });

        $overrideColumns = [
            'context_explanations_per_day', 'context_explanations_per_month',
            'grammar_explanations_per_day', 'grammar_explanations_per_month',
            'simplifications_per_day', 'simplifications_per_month',
            'max_context_explanation_characters', 'max_grammar_explanation_characters',
            'max_simplification_characters',
            'ai_context_explanation_enabled', 'ai_grammar_explanation_enabled',
            'ai_simplification_enabled', 'shadowing_enabled',
        ];

        Schema::table('user_ai_limit_overrides', function (Blueprint $table) use ($overrideColumns) {
            foreach ($overrideColumns as $column) {
                $table->dropColumn($column);
            }
        });
    }
};
