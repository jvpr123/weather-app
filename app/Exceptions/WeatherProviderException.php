<?php

namespace App\Exceptions;

use RuntimeException;

final class WeatherProviderException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly ?int $statusCode = null,
    ) {
        parent::__construct($message);
    }

    public static function configuration(): self
    {
        return new self('Weather provider authentication is not configured correctly.', 401);
    }

    public static function notFound(): self
    {
        return new self('The requested weather resource was not found.', 404);
    }

    public static function rateLimited(): self
    {
        return new self('The weather provider rate limit was exceeded.', 429);
    }

    public static function unavailable(?int $statusCode = null): self
    {
        return new self('The weather provider is temporarily unavailable.', $statusCode);
    }

    public static function network(): self
    {
        return new self('The weather provider could not be reached.');
    }

    public static function requestFailed(int $statusCode): self
    {
        return new self('The weather provider rejected the request.', $statusCode);
    }

    public static function invalidResponse(): self
    {
        return new self('The weather provider returned an invalid response.');
    }
}
