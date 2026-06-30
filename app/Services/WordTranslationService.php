<?php

namespace App\Services;

use App\Models\Book;
use App\Services\Intelligence\Translation\TranslationService;
use App\Services\Intelligence\Usage\AiUsageContext;
use RuntimeException;

class WordTranslationService
{
    public function __construct(
        private readonly TranslationService $translationService,
    ) {}

    public function translate(
        string $word,
        string $context,
        string $sourceLocale,
        string $nativeLocale,
        ?int $userId = null,
        string $privacyScope = 'private',
        ?int $scopeId = null,
    ): array {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('Translation service is not configured.');
        }

        $usageContext = $userId ? new AiUsageContext(userId: $userId) : null;
        $book = $scopeId ? Book::find($scopeId) : null;

        $translationResult = $this->translationService->translate(
            text: $word,
            context: $context,
            sourceLanguage: $sourceLocale,
            targetLanguage: $nativeLocale,
            userId: $userId,
            book: $book,
            usageContext: $usageContext,
        );

        return [
            'translation' => $translationResult['translation'],
            'pronunciation' => $translationResult['pronunciation'],
            'explanation' => null,
        ];
    }
}
