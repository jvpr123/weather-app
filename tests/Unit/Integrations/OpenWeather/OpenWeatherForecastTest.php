<?php

use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\ForecastData;
use App\Exceptions\WeatherProviderException;
use App\Integrations\OpenWeather\OpenWeatherClient;
use App\Integrations\OpenWeather\OpenWeatherProvider;
use App\Integrations\Redis\RedisWeatherCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function forecastProvider(): OpenWeatherProvider
{
    return new OpenWeatherProvider(
        client: new OpenWeatherClient(
            apiKey: 'test-api-key',
            baseUrl: 'https://api.openweathermap.test',
            retryTimes: 1,
            retryDelayMilliseconds: 0,
        ),
        cache: new RedisWeatherCache(new Repository(new ArrayStore)),
    );
}

function forecastPayload(): array
{
    return [
        'list' => [
            [
                'dt' => 1_725_194_400,
                'main' => [
                    'temp' => 25.3,
                    'temp_min' => 24.8,
                    'temp_max' => 25.3,
                ],
                'weather' => [[
                    'id' => 801,
                    'main' => 'Clouds',
                    'icon' => '02d',
                ]],
                'wind' => ['speed' => 2.8],
                'pop' => 0.15,
            ],
            [
                'dt' => 1_725_205_200,
                'main' => [
                    'temp' => 21.7,
                    'temp_min' => 21.7,
                    'temp_max' => 23.1,
                ],
                'weather' => [[
                    'id' => 500,
                    'main' => 'Rain',
                    'icon' => '10n',
                ]],
                'wind' => ['speed' => 4.1],
                'pop' => 0.72,
            ],
        ],
        'city' => ['timezone' => -10_800],
    ];
}

it('normalizes three-hour forecast periods without daily aggregation', function () {
    Http::fake(['*' => Http::response(forecastPayload())]);

    $forecast = forecastProvider()->forecast(new Coordinates(-23.5505, -46.6333));

    expect($forecast)->toBeInstanceOf(ForecastData::class)
        ->and($forecast->toArray())->toBe([
            'periods' => [
                [
                    'datetime' => 1_725_194_400,
                    'temperature' => 25.3,
                    'minTemperature' => 24.8,
                    'maxTemperature' => 25.3,
                    'condition' => 'Clouds',
                    'weatherCode' => 801,
                    'isDaytime' => true,
                    'probabilityOfPrecipitation' => 0.15,
                    'windSpeed' => 2.8,
                ],
                [
                    'datetime' => 1_725_205_200,
                    'temperature' => 21.7,
                    'minTemperature' => 21.7,
                    'maxTemperature' => 23.1,
                    'condition' => 'Rain',
                    'weatherCode' => 500,
                    'isDaytime' => false,
                    'probabilityOfPrecipitation' => 0.72,
                    'windSpeed' => 4.1,
                ],
            ],
            'timezoneOffset' => -10_800,
        ]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/data/2.5/forecast?')
        && str_contains($request->url(), 'lat=-23.5505')
        && str_contains($request->url(), 'lon=-46.6333')
        && str_contains($request->url(), 'units=metric')
        && str_contains($request->url(), 'lang=pt_br')
    );
});

it('supports an empty forecast period list', function () {
    Http::fake(['*' => Http::response([
        'list' => [],
        'city' => ['timezone' => 0],
    ])]);

    expect(forecastProvider()->forecast(new Coordinates(0, 0))->periods)->toBe([]);
});

it('rejects malformed forecast responses', function (Closure $mutate) {
    $payload = forecastPayload();
    $mutate($payload);
    Http::fake(['*' => Http::response($payload)]);

    expect(fn () => forecastProvider()->forecast(new Coordinates(0, 0)))
        ->toThrow(WeatherProviderException::class, 'invalid response');
})->with([
    'missing period list' => function (array &$payload): void {
        unset($payload['list']);
    },
    'invalid period list' => fn (array &$payload) => $payload['list'] = ['period' => []],
    'missing timezone' => function (array &$payload): void {
        unset($payload['city']['timezone']);
    },
    'missing period timestamp' => function (array &$payload): void {
        unset($payload['list'][0]['dt']);
    },
    'missing period temperature' => function (array &$payload): void {
        unset($payload['list'][0]['main']['temp']);
    },
    'invalid precipitation probability' => fn (array &$payload) => $payload['list'][0]['pop'] = 1.1,
    'missing period condition' => fn (array &$payload) => $payload['list'][0]['weather'] = [],
    'invalid period icon' => fn (array &$payload) => $payload['list'][0]['weather'][0]['icon'] = 'cloudy',
]);
