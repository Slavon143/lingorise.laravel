<?php

namespace App\Enums;

enum AiQuotaError: string
{
    case DailyTranslationLimitExceeded = 'daily_translation_limit_exceeded';
    case MonthlyTranslationLimitExceeded = 'monthly_translation_limit_exceeded';
    case DailyExplanationLimitExceeded = 'daily_explanation_limit_exceeded';
    case MonthlyExplanationLimitExceeded = 'monthly_explanation_limit_exceeded';
    case DailyContextExplanationLimitExceeded = 'daily_context_explanation_limit_exceeded';
    case MonthlyContextExplanationLimitExceeded = 'monthly_context_explanation_limit_exceeded';
    case DailyGrammarExplanationLimitExceeded = 'daily_grammar_explanation_limit_exceeded';
    case MonthlyGrammarExplanationLimitExceeded = 'monthly_grammar_explanation_limit_exceeded';
    case DailySimplificationLimitExceeded = 'daily_simplification_limit_exceeded';
    case MonthlySimplificationLimitExceeded = 'monthly_simplification_limit_exceeded';
    case DailyTtsLimitExceeded = 'daily_tts_limit_exceeded';
    case MonthlyTtsLimitExceeded = 'monthly_tts_limit_exceeded';
    case TranslationDisabled = 'translation_disabled';
    case ExplanationDisabled = 'explanation_disabled';
    case ContextExplanationDisabled = 'context_explanation_disabled';
    case GrammarExplanationDisabled = 'grammar_explanation_disabled';
    case SimplificationDisabled = 'simplification_disabled';
    case TtsDisabled = 'tts_disabled';
    case BudgetBlocked = 'budget_blocked';
}
