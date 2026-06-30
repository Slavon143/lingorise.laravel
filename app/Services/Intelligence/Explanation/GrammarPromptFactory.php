<?php

namespace App\Services\Intelligence\Explanation;

class GrammarPromptFactory
{
    public function systemPrompt(string $sourceLanguage, string $targetLanguage): string
    {
        $strategy = $this->detectStrategy($sourceLanguage);

        return match ($strategy) {
            'swedish' => $this->swedishPrompt($targetLanguage),
            'english' => $this->englishPrompt($targetLanguage),
            default => $this->genericPrompt($targetLanguage),
        };
    }

    public function detectStrategy(string $sourceLanguage): string
    {
        $lang = strtolower($sourceLanguage);

        return match ($lang) {
            'sv', 'swe', 'swedish' => 'swedish',
            'en', 'eng', 'english' => 'english',
            default => 'generic',
        };
    }

    private function englishPrompt(string $targetLanguage): string
    {
        return <<<PROMPT
You are a grammar tutor for English language learners.
Given a sentence in English and the learner's native language:
1. Identify the main grammar construction(s) used.
2. Explain why this construction is used and what it expresses.
3. Show the structure/formula (e.g., "had + been + verb-ing").
4. Break down the sentence parts and explain the role of each.
5. Provide a simplified translation into the learner's native language ({$targetLanguage}).
6. Give one additional example sentence.
7. Mention a typical mistake learners make with this construction.
Return only valid JSON with no additional text.
PROMPT;
    }

    private function swedishPrompt(string $targetLanguage): string
    {
        return <<<PROMPT
You are a grammar tutor for Swedish language learners.
Given a sentence in Swedish and the learner's native language ({$targetLanguage}):
1. Identify the main grammar construction(s).
2. Explain word order, especially V2 inversion if present.
3. Note en/ett gender if relevant.
4. Explain definite/indefinite form usage.
5. Note particles and their placement.
6. Explain adjective forms (strong/weak).
7. Show the structure.
8. Provide a simplified translation into {$targetLanguage}.
9. Give one additional example.
10. Mention a typical mistake.
Return only valid JSON with no additional text.
PROMPT;
    }

    private function genericPrompt(string $targetLanguage): string
    {
        return <<<PROMPT
You are a grammar tutor for language learners.
Given a sentence and the learner's native language ({$targetLanguage}):
1. Identify the main grammar construction(s) used.
2. Explain why this construction is used.
3. Show the structure/formula.
4. Break down the sentence parts and explain the role of each.
5. Provide a simplified translation into {$targetLanguage}.
6. Give one additional example sentence.
7. Mention a typical mistake learners make.
Return only valid JSON with no additional text.
PROMPT;
    }
}
