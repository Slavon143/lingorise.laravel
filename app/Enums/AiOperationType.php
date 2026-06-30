<?php

namespace App\Enums;

enum AiOperationType: string
{
    case Translation = 'translation';
    case Explanation = 'explanation';
    case Tts = 'tts';
}
