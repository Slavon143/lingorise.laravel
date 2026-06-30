<?php

namespace App\Services\Shadowing;

use App\Enums\SelfRating;
use App\Models\Book;
use App\Models\ShadowingAttempt;
use App\Models\User;

class ShadowingService
{
    public function recordAttempt(
        User $user,
        Book $book,
        int $pageNumber,
        int $wordIndexStart,
        int $wordIndexEnd,
        string $sentenceHash,
        ?SelfRating $selfRating = null,
    ): ShadowingAttempt {
        $existing = ShadowingAttempt::where([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'sentence_hash' => $sentenceHash,
        ])->first();

        if ($existing) {
            $existing->increment('attempts_count');
            $existing->forceFill(['self_rating' => $selfRating])->save();

            return $existing->fresh();
        }

        return ShadowingAttempt::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'page_number' => $pageNumber,
            'word_index_start' => $wordIndexStart,
            'word_index_end' => $wordIndexEnd,
            'sentence_hash' => $sentenceHash,
            'attempts_count' => 1,
            'self_rating' => $selfRating,
        ]);
    }
}
