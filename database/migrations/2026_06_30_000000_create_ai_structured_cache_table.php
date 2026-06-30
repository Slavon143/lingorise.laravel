<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_structured_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 64)->unique();
            $table->string('operation_type', 40)->index();
            $table->string('source_text', 2000);
            $table->string('source_text_hash', 40)->nullable()->index();
            $table->text('context_text')->nullable();
            $table->string('context_hash', 40)->nullable();
            $table->string('source_language', 12)->index();
            $table->string('target_language', 12)->nullable()->index();
            $table->string('target_level', 4)->nullable();
            $table->json('response_json');
            $table->string('model', 64);
            $table->string('provider', 32)->default('openai');
            $table->unsignedSmallInteger('prompt_version')->default(1);
            $table->unsignedSmallInteger('response_format_version')->default(1);
            $table->string('privacy_scope', 20)->nullable()->index();
            $table->unsignedInteger('scope_id')->nullable()->index();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->unsignedBigInteger('original_usage_event_id')->nullable();
            $table->timestamps();

            $table->index(['operation_type', 'source_language', 'target_language'], 'structured_cache_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_structured_cache');
    }
};
