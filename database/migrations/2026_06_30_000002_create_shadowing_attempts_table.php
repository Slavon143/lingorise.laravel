<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shadowing_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->integer('page_number');
            $table->integer('word_index_start');
            $table->integer('word_index_end');
            $table->string('sentence_hash', 64);
            $table->string('self_rating', 20)->nullable();
            $table->integer('attempts_count')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'book_id', 'page_number', 'sentence_hash'], 'shadowing_sentence_unique');
            $table->index(['user_id', 'book_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadowing_attempts');
    }
};
