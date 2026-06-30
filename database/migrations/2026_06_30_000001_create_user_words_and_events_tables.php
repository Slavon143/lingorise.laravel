<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('lemma', 255);
            $table->string('display_word', 255)->nullable();
            $table->string('language', 12);
            $table->text('translation')->nullable();
            $table->string('status', 20)->default('unknown');
            $table->decimal('mastery_score', 5, 2)->default(0);
            $table->integer('seen_count')->default(0);
            $table->integer('translation_count')->default(0);
            $table->integer('explanation_count')->default(0);
            $table->integer('correct_count')->default(0);
            $table->integer('incorrect_count')->default(0);
            $table->integer('listening_correct_count')->default(0);
            $table->integer('listening_incorrect_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lemma', 'language']);
            $table->index(['user_id', 'language']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('user_word_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_word_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('page_number')->nullable();
            $table->integer('word_index')->nullable();
            $table->string('context_hash', 64)->nullable();
            $table->decimal('score_delta', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type']);
            $table->index(['user_word_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_word_events');
        Schema::dropIfExists('user_words');
    }
};
