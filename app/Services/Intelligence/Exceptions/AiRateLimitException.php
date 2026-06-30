<?php

namespace App\Services\Intelligence\Exceptions;

use RuntimeException;

class AiRateLimitException extends RuntimeException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
