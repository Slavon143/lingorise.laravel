<?php

namespace App\Services\Intelligence\Subscription;

use App\Enums\AiQuotaError;
use RuntimeException;

class AiQuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly AiQuotaError $errorCode,
        string $message,
        public readonly ?\DateTimeInterface $resetsAt = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
