<?php

namespace App\Enums;

enum WordStatus: string
{
    case Unknown = 'unknown';
    case Seen = 'seen';
    case Learning = 'learning';
    case Familiar = 'familiar';
    case Known = 'known';
    case Mastered = 'mastered';
}
