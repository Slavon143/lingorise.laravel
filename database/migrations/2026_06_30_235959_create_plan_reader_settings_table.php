<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_reader_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->onDelete('cascade');

            $table->integer('translation_max_words')->default(10);
            $table->integer('context_max_words')->default(10);
            $table->integer('grammar_max_words')->default(10);
            $table->integer('simplify_max_words')->default(10);
            $table->integer('tts_max_words')->default(10);
            $table->integer('pronunciation_max_words')->default(10);
            $table->integer('vocabulary_max_words')->default(10);

            $table->integer('ai_actions_daily_limit')->default(10);
            $table->integer('ai_tts_monthly_characters')->nullable();

            $table->boolean('ai_tts_enabled')->default(false);
            $table->boolean('browser_tts_enabled')->default(true);
            $table->boolean('pronunciation_recording_enabled')->default(true);
            $table->boolean('shadowing_enabled')->default(false);
            $table->boolean('voice_selection_enabled')->default(false);

            $table->boolean('context_enabled')->default(true);
            $table->boolean('grammar_enabled')->default(true);
            $table->boolean('simplify_enabled')->default(true);
            $table->boolean('translation_enabled')->default(true);
            $table->boolean('vocabulary_enabled')->default(true);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique('plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_reader_settings');
    }
};