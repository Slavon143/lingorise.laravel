<?php

namespace App\Enums;

enum AiUsageStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case RateLimited = 'rate_limited';
    case Cancelled = 'cancelled';
    case BudgetBlocked = 'budget_blocked';
}
