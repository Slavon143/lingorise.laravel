<?php

namespace App\Services\Intelligence\Contracts;

use App\Services\Intelligence\Explanation\ContextExplanationData;
use App\Services\Intelligence\Explanation\ContextExplanationResult;
use App\Services\Intelligence\Explanation\ExplanationRequest;
use App\Services\Intelligence\Explanation\ExplanationResult;
use App\Services\Intelligence\Explanation\GrammarExplanationData;
use App\Services\Intelligence\Explanation\GrammarExplanationResult;
use App\Services\Intelligence\Explanation\SimplificationData;
use App\Services\Intelligence\Explanation\SimplificationResult;
use App\Services\Intelligence\Translation\TranslationRequest;
use App\Services\Intelligence\Translation\TranslationResult;
use App\Services\Intelligence\Tts\TtsRequest;
use App\Services\Intelligence\Tts\TtsResult;

interface AiProviderInterface
{
    public function translate(TranslationRequest $request): TranslationResult;
    public function explain(ExplanationRequest $request): ExplanationResult;
    public function synthesize(TtsRequest $request): TtsResult;
    public function explainInContext(ContextExplanationData $request): ContextExplanationResult;
    public function explainGrammar(GrammarExplanationData $request): GrammarExplanationResult;
    public function simplify(SimplificationData $request): SimplificationResult;
}
