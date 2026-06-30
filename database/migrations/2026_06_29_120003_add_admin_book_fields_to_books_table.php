<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->string('subtitle')->nullable()->after('slug');
            $table->text('short_description')->nullable()->after('subtitle');
            $table->longText('description')->nullable()->after('short_description');
            $table->foreignId('author_id')->nullable()->after('description')->constrained('authors')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('author_id')->constrained('categories')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->after('category_id')->constrained('languages')->nullOnDelete();
            $table->string('access_type', 16)->default('public')->index()->after('language_id');
            $table->string('difficulty', 32)->nullable()->index()->after('level');
            $table->boolean('is_featured')->default(false)->after('cover_path');
            $table->timestamp('published_at')->nullable()->after('is_featured');
            $table->timestamp('archived_at')->nullable()->after('published_at');
            $table->foreignId('created_by')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'slug', 'subtitle', 'short_description', 'description',
                'author_id', 'category_id', 'language_id',
                'access_type', 'difficulty', 'is_featured',
                'published_at', 'archived_at', 'created_by', 'user_id',
            ]);
        });
    }
};
