<?php

use App\Exceptions\WeatherProviderException;
use App\Integrations\OpenWeather\OpenWeatherClient;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function openWeatherClient(array $overrides = []): OpenWeatherClient
{
    $configuration = array_merge([
        'apiKey' => 'test-api-key',
        'baseUrl' => 'https://api.openweathermap.test/',
        'timeout' => 10,
        'connectTimeout' => 5,
        'retryTimes' => 1,
        'retryDelayMilliseconds' => 0,
    ], $overrides);

    return new OpenWeatherClient(...$configuration);
}

it('sends authenticated JSON requests and returns decoded payloads', function () {
    Http::fake([
        'api.openweathermap.test/*' => Http::response(['cod' => 200, 'name' => 'São Paulo']),
    ]);

    $payload = openWeatherClient()->get('/data/2.5/weather', [
        'lat' => -23.55,
        'lon' => -46.63,
    ]);

    expect($payload)->toMatchArray(['cod' => 200, 'name' => 'São Paulo']);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://api.openweathermap.test/data/2.5/weather?')
        && str_contains($request->url(), 'appid=test-api-key')
        && str_contains($request->url(), 'lat=-23.55')
        && str_contains($request->url(), 'lon=-46.63')
        && $request->hasHeader('Accept', 'application/json')
    );
});

it('requires an API key without sending a request', function () {
    Http::fake();

    expect(fn () => openWeatherClient(['apiKey' => ''])->get('/data/2.5/weather'))
        ->toThrow(WeatherProviderException::class, 'authentication is not configured');

    Http::assertNothingSent();
});

it('maps provider HTTP errors to sanitized internal exceptions', function (
    int $status,
    ?int $expectedStatus,
    string $expectedMessage,
    string $expectedCode,
) {
    Http::fake([
        '*' => Http::response(['message' => 'external payload containing test-api-key'], $status),
    ]);

    try {
        openWeatherClient()->get('/data/2.5/weather');
        $this->fail('A WeatherProviderException was not thrown.');
    } catch (WeatherProviderException $exception) {
        expect($exception->statusCode)->toBe($expectedStatus)
            ->and($exception->errorCode)->toBe($expectedCode)
            ->and($exception->getMessage())->toContain($expectedMessage)
            ->and($exception->getMessage())->not->toContain('test-api-key')
            ->and($exception->getMessage())->not->toContain('external payload');
    }
})->with([
    'authentication' => [401, 401, 'authentication', WeatherProviderException::UNAVAILABLE],
    'not found' => [404, 404, 'not found', WeatherProviderException::NOT_FOUND],
    'rate limit' => [429, 429, 'rate limit', WeatherProviderException::RATE_LIMITED],
    'provider unavailable' => [500, 500, 'temporarily unavailable', WeatherProviderException::UNAVAILABLE],
    'other client failure' => [422, 422, 'rejected the request', WeatherProviderException::UNAVAILABLE],
]);

it('retries transient provider failures', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['message' => 'temporarily unavailable'], 500)
            ->push(['cod' => 200], 200),
    ]);

    $payload = openWeatherClient(['retryTimes' => 2])->get('/data/2.5/weather');

    expect($payload)->toBe(['cod' => 200]);
    Http::assertSentCount(2);
});

it('maps connection failures to a network exception', function () {
    Http::fake(['*' => Http::failedConnection()]);

    try {
        openWeatherClient()->get('/data/2.5/weather');
        $this->fail('A WeatherProviderException was not thrown.');
    } catch (WeatherProviderException $exception) {
        expect($exception->getMessage())->toBe('The weather provider could not be reached.')
            ->and($exception->getPrevious())->toBeNull()
            ->and($exception->getMessage())->not->toContain('appid');
    }
});

it('maps connection timeouts without exposing transport details', function () {
    Http::fake(function () {
        $request = new PsrRequest('GET', 'https://api.openweathermap.test/data');
        $transportException = new ConnectTimeoutException(
            'cURL error 28 containing appid=test-api-key',
            $request,
        );

        throw new ConnectionException(
            $transportException->getMessage(),
            previous: $transportException,
        );
    });

    try {
        openWeatherClient()->get('/data/2.5/weather');
        $this->fail('A WeatherProviderException was not thrown.');
    } catch (WeatherProviderException $exception) {
        expect($exception->errorCode)->toBe(WeatherProviderException::TIMEOUT)
            ->and($exception->getMessage())->toBe('The weather provider request timed out.')
            ->and($exception->getPrevious())->toBeNull()
            ->and($exception->getMessage())->not->toContain('cURL')
            ->and($exception->getMessage())->not->toContain('appid');
    }
});

it('rejects invalid JSON payloads', function () {
    Http::fake([
        '*' => Http::response('not-json', 200, ['Content-Type' => 'application/json']),
    ]);

    expect(fn () => openWeatherClient()->get('/data/2.5/weather'))
        ->toThrow(WeatherProviderException::class, 'invalid response');
});

it('normalizes its configured base URL', function () {
    expect(openWeatherClient()->baseUrl())->toBe('https://api.openweathermap.test');
});
