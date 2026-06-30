<?php

namespace App\Services\Intelligence\Exceptions;

use RuntimeException;

class AiBudgetExceededException extends RuntimeException
{
    public function __construct(
        string $message = 'Monthly AI budget exceeded',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
