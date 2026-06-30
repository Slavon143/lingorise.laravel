<?php

namespace App\Enums;

enum SelfRating: string
{
    case Easy = 'easy';
    case Okay = 'okay';
    case Difficult = 'difficult';
    case AlmostCorrect = 'almost_correct';
    case Good = 'good';
}
