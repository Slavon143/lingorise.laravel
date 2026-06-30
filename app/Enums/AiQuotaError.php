<?php

namespace App\Enums;

enum AiQuotaError: string
{
    case DailyTranslationLimitExceeded = 'daily_translation_limit_exceeded';
    case MonthlyTranslationLimitExceeded = 'monthly_translation_limit_exceeded';
    case DailyExplanationLimitExceeded = 'daily_explanation_limit_exceeded';
    case MonthlyExplanationLimitExceeded = 'monthly_explanation_limit_exceeded';
    case DailyTtsLimitExceeded = 'daily_tts_limit_exceeded';
    case MonthlyTtsLimitExceeded = 'monthly_tts_limit_exceeded';
    case TranslationDisabled = 'translation_disabled';
    case ExplanationDisabled = 'explanation_disabled';
    case TtsDisabled = 'tts_disabled';
    case BudgetBlocked = 'budget_blocked';
}
