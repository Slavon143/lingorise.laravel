<?php

namespace App\Enums;

enum TtsCacheStatus: string
{
    case Generating = 'generating';
    case Ready = 'ready';
    case Failed = 'failed';
    case Missing = 'missing';
    case Corrupted = 'corrupted';
}
