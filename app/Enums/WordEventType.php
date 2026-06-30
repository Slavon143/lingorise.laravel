<?php

namespace App\Enums;

enum WordEventType: string
{
    case Seen = 'seen';
    case Translated = 'translated';
    case Explained = 'explained';
    case GrammarExplained = 'grammar_explained';
    case Saved = 'saved';
    case QuizCorrect = 'quiz_correct';
    case QuizIncorrect = 'quiz_incorrect';
    case ListeningCorrect = 'listening_correct';
    case ListeningIncorrect = 'listening_incorrect';
    case ManualKnown = 'manual_known';
    case ManualUnknown = 'manual_unknown';
    case ShadowingPracticed = 'shadowing_practiced';
}
