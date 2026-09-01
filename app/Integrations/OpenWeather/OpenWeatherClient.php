<?php

namespace App\Integrations\OpenWeather;

use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class OpenWeatherClient
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private int $timeout = 10,
        private int $connectTimeout = 5,
        private int $retryTimes = 3,
        private int $retryDelayMilliseconds = 200,
    ) {}

    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>
     *
     * @throws WeatherProviderException
     */
    public function get(string $endpoint, array $query = []): array
    {
        if (trim($this->apiKey) === '') {
            throw WeatherProviderException::configuration();
        }

        try {
            $response = $this->request()->get($endpoint, $query);
        } catch (ConnectionException) {
            throw WeatherProviderException::network();
        }

        $this->ensureSuccessful($response);

        $payload = $response->json();

        if (! is_array($payload)) {
            throw WeatherProviderException::invalidResponse();
        }

        return $payload;
    }

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withQueryParameters(['appid' => $this->apiKey])
            ->acceptJson()
            ->timeout(max(1, $this->timeout))
            ->connectTimeout(max(1, $this->connectTimeout))
            ->retry(
                max(1, $this->retryTimes),
                max(0, $this->retryDelayMilliseconds),
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
                throw: false,
            );
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        return $exception->response->status() === 429 || $exception->response->serverError();
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw match (true) {
            $response->status() === 401 => WeatherProviderException::configuration(),
            $response->status() === 404 => WeatherProviderException::notFound(),
            $response->status() === 429 => WeatherProviderException::rateLimited(),
            $response->serverError() => WeatherProviderException::unavailable($response->status()),
            default => WeatherProviderException::requestFailed($response->status()),
        };
    }
}
