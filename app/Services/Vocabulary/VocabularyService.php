<?php

namespace App\Services\Vocabulary;

use App\Models\DictionaryEntry;
use App\Models\User;
use App\Models\UserWord;
use App\Services\ContentHashService;

class VocabularyService
{
    public function __construct(
        private readonly WordMasteryService $mastery,
        private readonly WordEventService $eventService,
        private readonly ContentHashService $hashes,
    ) {}

    public function ensureWord(
        User $user,
        string $lemma,
        string $displayWord,
        string $language,
        ?string $translation = null,
    ): UserWord {
        $normalizedLemma = mb_strtolower($this->hashes->normalize($lemma));

        return UserWord::firstOrCreate(
            [
                'user_id' => $user->id,
                'language' => $language,
                'lemma' => $normalizedLemma,
            ],
            [
                'display_word' => $displayWord,
                'translation' => $translation,
                'status' => \App\Enums\WordStatus::Unknown,
            ],
        );
    }

    public function migrateFromDictionary(User $user, DictionaryEntry $entry): ?UserWord
    {
        $lemma = mb_strtolower($this->hashes->normalize($entry->original_text));

        $word = UserWord::where('user_id', $user->id)
            ->where('language', $entry->book?->language_locale ?? 'en')
            ->where('lemma', $lemma)
            ->first();

        if ($word) {
            return $word;
        }

        return UserWord::create([
            'user_id' => $user->id,
            'language' => $entry->book?->language_locale ?? 'en',
            'lemma' => $lemma,
            'display_word' => $entry->original_text,
            'translation' => $entry->translated_text,
            'status' => \App\Enums\WordStatus::Seen,
            'seen_count' => 1,
            'translation_count' => 1,
            'mastery_score' => 1.5,
            'last_seen_at' => $entry->created_at,
        ]);
    }

    public function decayAllWords(?int $daysInactive = null): int
    {
        $count = 0;
        UserWord::where('mastery_score', '>', 0)->chunk(100, function ($words) use ($daysInactive, &$count): void {
            foreach ($words as $word) {
                $this->mastery->calculateDecay($word, $daysInactive);
                $word->save();
                $count++;
            }
        });

        return $count;
    }
}
