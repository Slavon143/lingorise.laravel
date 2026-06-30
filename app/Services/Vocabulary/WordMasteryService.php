<?php

namespace App\Services\Vocabulary;

use App\Enums\WordEventType;
use App\Enums\WordStatus;
use App\Models\UserWord;

class WordMasteryService
{
    private const array WEIGHTS = [
        WordEventType::Seen->value => 0.5,
        WordEventType::Translated->value => 1.0,
        WordEventType::Explained->value => 2.0,
        WordEventType::GrammarExplained->value => 2.0,
        WordEventType::Saved->value => 1.0,
        WordEventType::QuizCorrect->value => 5.0,
        WordEventType::QuizIncorrect->value => -3.0,
        WordEventType::ListeningCorrect->value => 4.0,
        WordEventType::ListeningIncorrect->value => -2.0,
        WordEventType::ManualKnown->value => 10.0,
        WordEventType::ManualUnknown->value => -5.0,
        WordEventType::ShadowingPracticed->value => 1.0,
    ];

    public function applyEvent(UserWord $word, WordEventType $eventType, ?float $scoreDelta = null): UserWord
    {
        $delta = $scoreDelta ?? ($this::WEIGHTS[$eventType->value] ?? 0);

        $word->mastery_score = max(0, min(100, $word->mastery_score + $delta));
        $word->status = $this->determineStatus($word);

        return $word;
    }

    public function determineStatus(UserWord $word): WordStatus
    {
        $score = $word->mastery_score;

        return match (true) {
            $score >= 90 => WordStatus::Mastered,
            $score >= 70 => WordStatus::Known,
            $score >= 50 => WordStatus::Familiar,
            $score >= 25 => WordStatus::Learning,
            $word->seen_count > 0 => WordStatus::Seen,
            default => WordStatus::Unknown,
        };
    }

    public function calculateDecay(UserWord $word, ?int $daysInactive = null): UserWord
    {
        $daysInactive ??= $word->last_practiced_at
            ? (int) now()->diffInDays($word->last_practiced_at)
            : 0;

        if ($daysInactive < 7) {
            return $word;
        }

        $decayRate = match (true) {
            $daysInactive >= 90 => 0.5,
            $daysInactive >= 30 => 0.3,
            $daysInactive >= 14 => 0.15,
            $daysInactive >= 7 => 0.05,
            default => 0,
        };

        $decay = $word->mastery_score * $decayRate;
        if ($decay > 0) {
            $word->mastery_score = max(0, $word->mastery_score - $decay);
            $word->status = $this->determineStatus($word);
        }

        return $word;
    }

    public function incrementCounter(UserWord $word, WordEventType $eventType): UserWord
    {
        return match ($eventType) {
            WordEventType::Seen => $word->increment('seen_count'),
            WordEventType::Translated => $word->increment('translation_count'),
            WordEventType::Explained, WordEventType::GrammarExplained => $word->increment('explanation_count'),
            WordEventType::QuizCorrect, WordEventType::ListeningCorrect => $word->increment('correct_count'),
            WordEventType::QuizIncorrect, WordEventType::ListeningIncorrect => $word->increment('incorrect_count'),
            default => $word,
        };
    }
}
