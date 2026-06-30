<?php

namespace App\Http\Responses;

use App\Services\Intelligence\Exceptions\AiBudgetExceededException;
use App\Services\Intelligence\Exceptions\AiInvalidResponseException;
use App\Services\Intelligence\Exceptions\AiProviderException;
use App\Services\Intelligence\Exceptions\AiRateLimitException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

class AiErrorResponse
{
    public static function fromException(\Throwable $exception, ?string $defaultMessage = null): JsonResponse
    {
        return match ($exception::class) {
            AiInvalidResponseException::class => self::json(502, 'invalid_ai_response', $exception->getMessage() ?: 'The AI response could not be processed.'),
            AiProviderException::class => self::json(503, 'provider_unavailable', $exception->getMessage() ?: 'The AI service is temporarily unavailable.'),
            AiBudgetExceededException::class => self::json(429, 'quota_exceeded', $exception->getMessage() ?: 'Your AI limit has been reached.'),
            AiRateLimitException::class => self::json(429, 'provider_rate_limit', 'Too many requests. Please try again later.'),
            ConnectionException::class => self::json(503, 'provider_connection_failed', 'Could not connect to the AI provider. Check your internet connection or DNS settings and try again.'),
            default => self::json(503, 'service_unavailable', $defaultMessage ?? 'Service temporarily unavailable.'),
        };
    }

    private static function json(int $status, string $error, string $message): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $error,
            'message' => $message,
        ];

        if (config('app.debug')) {
            $response['debug_code'] = $error;
        }

        return response()->json($response, $status);
    }
}
