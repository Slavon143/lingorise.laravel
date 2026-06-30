<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns to existing translation_cache
        Schema::table('translation_cache', function (Blueprint $table): void {
            $table->string('source_text_hash', 64)->nullable()->after('source_text');
            $table->string('provider', 32)->default('openai')->after('model');
            $table->string('mode', 32)->nullable()->after('prompt_version');
            $table->unsignedTinyInteger('response_format_version')->default(1)->after('mode');
            $table->unsignedInteger('source_characters')->nullable()->after('response_format_version');
            $table->unsignedInteger('response_characters')->nullable()->after('source_characters');
            $table->unsignedBigInteger('original_usage_event_id')->nullable()->after('response_characters');

            $table->index('model', 'tc_model_idx');
        });

        // Add new columns to existing explanation_cache
        Schema::table('explanation_cache', function (Blueprint $table): void {
            $table->string('selected_text_hash', 64)->nullable()->after('selected_text');
            $table->string('context_hash', 64)->nullable()->after('context_text');
            $table->string('provider', 32)->default('openai')->after('model');
            $table->string('mode', 32)->nullable()->after('prompt_version');
            $table->unsignedTinyInteger('response_format_version')->default(1)->after('mode');

            $table->index('model', 'ec_model_idx');
            $table->index('created_at', 'ec_created_at_idx');
        });

        // Add new columns to existing tts_cache
        Schema::table('tts_cache', function (Blueprint $table): void {
            $table->string('source_text_hash', 64)->nullable()->after('source_text');
            $table->string('provider', 32)->default('openai')->after('model');
            $table->unsignedSmallInteger('sample_rate')->nullable()->after('format');
            $table->string('status', 24)->default('ready')->after('sample_rate')->index('ts_status_idx');
            $table->unsignedTinyInteger('generation_attempts')->default(0)->after('status');
            $table->string('error_code', 64)->nullable()->after('generation_attempts');
            $table->string('error_message', 512)->nullable()->after('error_code');
            $table->unsignedBigInteger('original_usage_event_id')->nullable()->after('error_message');

            $table->index('model', 'ts_model_idx');
            $table->index('voice', 'ts_voice_idx');
        });

        // Create ai_usage_events table
        Schema::create('ai_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation_type', 32)->index();
            $table->string('provider', 32);
            $table->string('model');
            $table->string('cache_key', 64)->nullable()->index();
            $table->boolean('cache_hit')->default(false)->index();
            $table->boolean('provider_called')->default(false)->index();
            $table->unsignedInteger('request_characters')->default(0);
            $table->unsignedInteger('response_characters')->default(0);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('cached_input_tokens')->nullable();
            $table->unsignedInteger('audio_input_tokens')->nullable();
            $table->unsignedInteger('audio_output_tokens')->nullable();
            $table->unsignedInteger('audio_duration_ms')->nullable();
            $table->unsignedInteger('audio_size_bytes')->nullable();
            $table->string('pricing_version', 32)->nullable();
            $table->string('cost_calculation_type', 32);
            $table->decimal('estimated_cost_usd', 14, 8)->default(0);
            $table->decimal('actual_cost_usd', 14, 8)->nullable();
            $table->decimal('saved_cost_usd', 14, 8)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('provider_duration_ms')->nullable();
            $table->string('status', 32)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('safe_error_message', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'created_at']);
            $table->index(['book_id', 'created_at']);
            $table->index(['operation_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_events');

        Schema::table('tts_cache', function (Blueprint $table): void {
            $table->dropColumn([
                'source_text_hash', 'provider', 'sample_rate', 'status',
                'generation_attempts', 'error_code', 'error_message',
                'original_usage_event_id',
            ]);
            $table->dropIndex('ts_model_idx');
            $table->dropIndex('ts_voice_idx');
        });

        Schema::table('explanation_cache', function (Blueprint $table): void {
            $table->dropColumn([
                'selected_text_hash', 'context_hash', 'provider', 'mode',
                'response_format_version',
            ]);
            $table->dropIndex('ec_model_idx');
            $table->dropIndex('ec_created_at_idx');
        });

        Schema::table('translation_cache', function (Blueprint $table): void {
            $table->dropColumn([
                'source_text_hash', 'provider', 'mode', 'response_format_version',
                'source_characters', 'response_characters', 'original_usage_event_id',
            ]);
            $table->dropIndex('tc_model_idx');
        });
    }
};
