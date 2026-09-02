<?php

use App\DTOs\Location\Coordinates;
use App\Integrations\OpenWeather\OpenWeatherClient;
use App\Integrations\OpenWeather\OpenWeatherProvider;
use App\Integrations\Redis\RedisWeatherCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function cachedOpenWeatherProvider(): OpenWeatherProvider
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

it('caches identical geocoding searches', function () {
    Http::fake(['*' => Http::response([[
        'name' => 'London',
        'country' => 'GB',
        'lat' => 51.5072,
        'lon' => -0.1276,
    ]])]);
    $provider = cachedOpenWeatherProvider();

    $first = $provider->search(' London ');
    $second = $provider->search('london');

    expect($second)->toEqual($first);
    Http::assertSentCount(1);
});

it('caches current weather and can force a refresh', function () {
    Http::fake(['*' => Http::response([
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
    ])]);
    $provider = cachedOpenWeatherProvider();
    $coordinates = new Coordinates(-23.5505, -46.6333);

    $first = $provider->current($coordinates);
    $second = $provider->current($coordinates);
    $refreshed = $provider->current($coordinates, forceRefresh: true);

    expect($second)->toEqual($first)
        ->and($refreshed)->toEqual($first);
    Http::assertSentCount(2);
});

it('caches forecast and can force a refresh', function () {
    Http::fake(['*' => Http::response([
        'list' => [[
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
        ]],
        'city' => ['timezone' => -10_800],
    ])]);
    $provider = cachedOpenWeatherProvider();
    $coordinates = new Coordinates(-23.5505, -46.6333);

    $first = $provider->forecast($coordinates);
    $second = $provider->forecast($coordinates);
    $refreshed = $provider->forecast($coordinates, forceRefresh: true);

    expect($second)->toEqual($first)
        ->and($refreshed)->toEqual($first);
    Http::assertSentCount(2);
});
