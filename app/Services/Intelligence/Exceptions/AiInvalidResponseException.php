<?php

namespace App\Services\Intelligence\Exceptions;

use RuntimeException;

class AiInvalidResponseException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid AI provider response',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
