<?php

namespace App\Exceptions;

use RuntimeException;

final class WeatherProviderException extends RuntimeException
{
    public const NOT_FOUND = 'weather_not_found';

    public const RATE_LIMITED = 'weather_rate_limited';

    public const TIMEOUT = 'weather_timeout';

    public const NETWORK_ERROR = 'weather_network_error';

    public const UNAVAILABLE = 'weather_unavailable';

    private function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly string $errorCode = self::UNAVAILABLE,
    ) {
        parent::__construct($message);
    }

    public static function configuration(): self
    {
        return new self('Weather provider authentication is not configured correctly.', 401);
    }

    public static function notFound(): self
    {
        return new self('The requested weather resource was not found.', 404, self::NOT_FOUND);
    }

    public static function rateLimited(): self
    {
        return new self('The weather provider rate limit was exceeded.', 429, self::RATE_LIMITED);
    }

    public static function unavailable(?int $statusCode = null): self
    {
        return new self('The weather provider is temporarily unavailable.', $statusCode);
    }

    public static function network(): self
    {
        return new self('The weather provider could not be reached.', errorCode: self::NETWORK_ERROR);
    }

    public static function timeout(): self
    {
        return new self('The weather provider request timed out.', errorCode: self::TIMEOUT);
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
