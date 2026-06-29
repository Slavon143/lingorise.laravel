<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_cache', function (Blueprint $table): void {
            $table->id();
            $table->string('cache_key', 64)->unique();
            $table->string('source_text', 1024);
            $table->string('source_language', 24)->index();
            $table->string('target_language', 24)->index();
            $table->string('translated_text', 1024);
            $table->string('pronunciation', 255)->nullable();
            $table->string('model');
            $table->unsignedSmallInteger('prompt_version');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('explanation_cache', function (Blueprint $table): void {
            $table->id();
            $table->string('cache_key', 64)->unique();
            $table->string('selected_text', 1024);
            $table->text('context_text');
            $table->string('source_language', 24)->index();
            $table->string('target_language', 24)->index();
            $table->text('explanation_text');
            $table->string('model');
            $table->unsignedSmallInteger('prompt_version');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tts_cache', function (Blueprint $table): void {
            $table->id();
            $table->string('cache_key', 64)->unique();
            $table->string('source_text', 1024);
            $table->string('language', 24)->index();
            $table->string('voice', 64);
            $table->string('speed', 16)->default('1');
            $table->string('model');
            $table->string('format', 16)->default('mp3');
            $table->string('file_path');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('cache_key', 64)->nullable()->index();
            $table->unsignedInteger('characters_count')->default(0);
            $table->boolean('cache_hit')->default(false)->index();
            $table->string('model')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status', 32)->index();
            $table->string('error_code')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('tts_cache');
        Schema::dropIfExists('explanation_cache');
        Schema::dropIfExists('translation_cache');
    }
};
