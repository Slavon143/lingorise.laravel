<?php

namespace App\Enums;

enum CostCalculationType: string
{
    case Actual = 'actual';
    case ProviderUsage = 'provider_usage';
    case TokenEstimate = 'token_estimate';
    case CharacterEstimate = 'character_estimate';
    case DurationEstimate = 'duration_estimate';
    case CacheReference = 'cache_reference';
    case Unknown = 'unknown';
}
