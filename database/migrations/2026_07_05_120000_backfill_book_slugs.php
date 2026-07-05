<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $usedSlugs = DB::table('books')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->pluck('slug')
            ->mapWithKeys(fn (string $slug): array => [$slug => true])
            ->all();

        DB::table('books')
            ->where(function ($query): void {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->select(['id', 'title'])
            ->chunkById(100, function ($books) use (&$usedSlugs): void {
                foreach ($books as $book) {
                    $baseSlug = Str::slug((string) $book->title) ?: 'book-' . $book->id;
                    $slug = $baseSlug;
                    $counter = 2;

                    while (isset($usedSlugs[$slug])) {
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    DB::table('books')
                        ->where('id', $book->id)
                        ->update(['slug' => $slug]);

                    $usedSlugs[$slug] = true;
                }
            });
    }

    public function down(): void
    {
        // Intentionally keep generated slugs. They are now part of the canonical book URLs.
    }
};
