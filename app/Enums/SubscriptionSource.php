<?php

namespace App\Enums;

enum SubscriptionSource: string
{
    case Manual = 'manual';
    case System = 'system';
    case Stripe = 'stripe';
    case Promotion = 'promotion';
}
