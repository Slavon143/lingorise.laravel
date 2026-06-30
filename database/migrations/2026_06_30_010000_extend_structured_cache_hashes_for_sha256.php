<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_structured_cache', function (Blueprint $table): void {
            $table->string('source_text_hash', 64)->nullable()->change();
            $table->string('context_hash', 64)->nullable()->change();
            $table->index(['operation_type', 'source_text_hash'], 'structured_feature_input_hash');
            $table->index(['operation_type', 'context_hash'], 'structured_feature_context_hash');
        });
    }

    public function down(): void
    {
        Schema::table('ai_structured_cache', function (Blueprint $table): void {
            $table->dropIndex('structured_feature_input_hash');
            $table->dropIndex('structured_feature_context_hash');
            $table->string('source_text_hash', 40)->nullable()->change();
            $table->string('context_hash', 40)->nullable()->change();
        });
    }
};
