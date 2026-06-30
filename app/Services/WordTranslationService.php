<?php

namespace App\Services;

use App\Models\Book;
use App\Services\Intelligence\Cache\AiTextNormalizer;
use App\Services\Intelligence\Cache\TranslationCacheRepository;
use App\Services\Intelligence\Cache\ExplanationCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Cost\AiCostCalculator;
use App\Services\Intelligence\Exceptions\AiBudgetExceededException;
use App\Services\Intelligence\Exceptions\AiProviderException;
use App\Services\Intelligence\Translation\TranslationService;
use App\Services\Intelligence\Explanation\ExplanationService;
use App\Services\Intelligence\Usage\AiUsageContext;
use App\Services\Intelligence\Usage\AiUsageRecorder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class WordTranslationService
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly ExplanationService $explanationService,
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

        try {
            $translationResult = $this->translationService->translate(
                text: $word,
                context: $context,
                sourceLanguage: $sourceLocale,
                targetLanguage: $nativeLocale,
                userId: $userId,
                book: $book,
                usageContext: $usageContext,
            );

            $explanationResult = $this->explanationService->explain(
                selectedText: $word,
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
                'explanation' => $explanationResult['explanation'],
            ];
        } catch (AiBudgetExceededException | AiProviderException $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
