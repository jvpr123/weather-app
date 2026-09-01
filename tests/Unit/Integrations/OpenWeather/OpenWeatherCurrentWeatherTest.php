<?php

use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\CurrentWeatherData;
use App\Exceptions\WeatherProviderException;
use App\Integrations\OpenWeather\OpenWeatherClient;
use App\Integrations\OpenWeather\OpenWeatherProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function currentWeatherProvider(): OpenWeatherProvider
{
    return new OpenWeatherProvider(new OpenWeatherClient(
        apiKey: 'test-api-key',
        baseUrl: 'https://api.openweathermap.test',
        retryTimes: 1,
        retryDelayMilliseconds: 0,
    ));
}

function currentWeatherPayload(): array
{
    return [
        'weather' => [[
            'id' => 800,
            'main' => 'Clear',
            'description' => 'céu limpo',
            'icon' => '01d',
        ]],
        'main' => [
            'temp' => 28.4,
            'feels_like' => 29.7,
            'temp_min' => 25.2,
            'temp_max' => 31.1,
            'pressure' => 1015,
            'humidity' => 55,
        ],
        'wind' => ['speed' => 3.4],
        'dt' => 1_725_193_800,
        'sys' => [
            'sunrise' => 1_725_166_800,
            'sunset' => 1_725_209_400,
        ],
    ];
}

it('normalizes the current weather response', function () {
    Http::fake(['*' => Http::response(currentWeatherPayload())]);

    $weather = currentWeatherProvider()->current(new Coordinates(-23.5505, -46.6333));

    expect($weather)->toBeInstanceOf(CurrentWeatherData::class)
        ->and($weather->toArray())->toBe([
            'temperature' => 28.4,
            'feelsLike' => 29.7,
            'minTemperature' => 25.2,
            'maxTemperature' => 31.1,
            'humidity' => 55,
            'pressure' => 1015,
            'windSpeed' => 3.4,
            'weatherCode' => 800,
            'condition' => 'Clear',
            'description' => 'céu limpo',
            'icon' => '01d',
            'sunrise' => 1_725_166_800,
            'sunset' => 1_725_209_400,
            'timestamp' => 1_725_193_800,
        ]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/data/2.5/weather?')
        && str_contains($request->url(), 'lat=-23.5505')
        && str_contains($request->url(), 'lon=-46.6333')
        && str_contains($request->url(), 'units=metric')
        && str_contains($request->url(), 'lang=pt_br')
    );
});

it('rejects malformed current weather responses', function (Closure $mutate) {
    $payload = currentWeatherPayload();
    $mutate($payload);
    Http::fake(['*' => Http::response($payload)]);

    expect(fn () => currentWeatherProvider()->current(new Coordinates(0, 0)))
        ->toThrow(WeatherProviderException::class, 'invalid response');
})->with([
    'missing main data' => function (array &$payload): void {
        unset($payload['main']);
    },
    'missing temperature' => function (array &$payload): void {
        unset($payload['main']['temp']);
    },
    'missing wind speed' => function (array &$payload): void {
        unset($payload['wind']['speed']);
    },
    'missing condition' => fn (array &$payload) => $payload['weather'] = [],
    'invalid condition description' => fn (array &$payload) => $payload['weather'][0]['description'] = null,
    'missing sunrise' => function (array &$payload): void {
        unset($payload['sys']['sunrise']);
    },
    'missing timestamp' => function (array &$payload): void {
        unset($payload['dt']);
    },
]);
