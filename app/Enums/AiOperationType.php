<?php

namespace App\Enums;

enum AiOperationType: string
{
    case Translation = 'translation';
    case Explanation = 'explanation';
    case Tts = 'tts';
    case ContextExplanation = 'context_explanation';
    case GrammarExplanation = 'grammar_explanation';
    case Simplification = 'simplification';
    case ChapterQuizGeneration = 'chapter_quiz_generation';
    case PronunciationAnalysis = 'pronunciation_analysis';
}
