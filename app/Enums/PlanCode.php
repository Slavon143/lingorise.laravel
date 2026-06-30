<?php

namespace App\Enums;

enum PlanCode: string
{
    case Free = 'free';
    case Premium = 'premium';
    case Admin = 'admin';
}
