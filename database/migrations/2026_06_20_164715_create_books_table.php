<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('language_locale', 10)->index();
            $table->string('level', 32)->nullable()->index();
            $table->string('source_type', 16)->default('text');
            $table->string('visibility', 16)->default('private')->index();
            $table->longText('content');
            $table->unsignedInteger('total_words')->default(0);
            $table->string('processing_status', 16)->default('ready')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
