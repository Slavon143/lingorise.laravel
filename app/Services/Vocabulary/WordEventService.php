<?php

namespace App\Services\Vocabulary;

use App\Enums\WordEventType;
use App\Models\Book;
use App\Models\User;
use App\Models\UserWord;
use App\Models\UserWordEvent;
use App\Services\ContentHashService;

class WordEventService
{
    public function __construct(
        private readonly WordMasteryService $mastery,
        private readonly ContentHashService $hashes,
    ) {}

    public function record(
        User $user,
        string $lemma,
        string $displayWord,
        string $language,
        WordEventType $eventType,
        ?Book $book = null,
        ?int $pageNumber = null,
        ?int $wordIndex = null,
        ?string $contextHash = null,
    ): UserWord {
        $normalizedLemma = mb_strtolower($this->hashes->normalize($lemma));

        $word = UserWord::firstOrCreate(
            [
                'user_id' => $user->id,
                'language' => $language,
                'lemma' => $normalizedLemma,
            ],
            [
                'display_word' => $displayWord,
                'status' => \App\Enums\WordStatus::Unknown,
                'last_seen_at' => now(),
            ],
        );

        $this->mastery->incrementCounter($word, $eventType);
        $this->mastery->applyEvent($word, $eventType);
        $word->last_seen_at = now();
        $word->save();

        UserWordEvent::create([
            'user_id' => $user->id,
            'user_word_id' => $word->id,
            'book_id' => $book?->id,
            'page_number' => $pageNumber,
            'word_index' => $wordIndex,
            'event_type' => $eventType->value,
            'context_hash' => $contextHash,
            'score_delta' => $this->mastery->applyEvent(clone $word, $eventType)->mastery_score - $word->mastery_score,
        ]);

        return $word->fresh();
    }

    public function setManualStatus(User $user, UserWord $word, WordEventType $eventType): UserWord
    {
        $this->mastery->applyEvent($word, $eventType);
        $word->save();

        UserWordEvent::create([
            'user_id' => $user->id,
            'user_word_id' => $word->id,
            'event_type' => $eventType->value,
        ]);

        return $word->fresh();
    }
}
