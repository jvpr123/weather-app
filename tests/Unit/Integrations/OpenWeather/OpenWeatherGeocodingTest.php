<?php

use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
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

function geocodingProvider(): OpenWeatherProvider
{
    return new OpenWeatherProvider(new OpenWeatherClient(
        apiKey: 'test-api-key',
        baseUrl: 'https://api.openweathermap.test',
        retryTimes: 1,
        retryDelayMilliseconds: 0,
    ));
}

it('normalizes OpenWeather geocoding results', function () {
    Http::fake([
        '*' => Http::response([
            [
                'name' => 'São Paulo',
                'local_names' => ['pt' => 'São Paulo'],
                'state' => 'São Paulo',
                'country' => 'br',
                'lat' => -23.5506507,
                'lon' => -46.6333824,
            ],
            [
                'name' => 'São Paulo de Olivença',
                'country' => 'BR',
                'lat' => -3.37833,
                'lon' => -68.8725,
            ],
        ]),
    ]);

    $locations = geocodingProvider()->search(' São Paulo ');

    expect($locations)->toHaveCount(2)
        ->and($locations[0])->toBeInstanceOf(LocationData::class)
        ->and($locations[0]->toArray())->toBe([
            'name' => 'São Paulo',
            'state' => 'São Paulo',
            'country' => 'BR',
            'latitude' => -23.5506507,
            'longitude' => -46.6333824,
        ])
        ->and($locations[1]->state)->toBeNull();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/geo/1.0/direct?')
        && str_contains($request->url(), 'q=S%C3%A3o%20Paulo')
        && str_contains($request->url(), 'limit=5')
    );
});

it('caps the external result limit', function () {
    Http::fake(['*' => Http::response([])]);

    geocodingProvider()->search('London', 20);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'limit=5'));
});

it('normalizes an OpenWeather reverse geocoding result', function () {
    Http::fake([
        '*' => Http::response([[
            'name' => 'São Paulo',
            'state' => 'São Paulo',
            'country' => 'br',
            'lat' => -23.5505,
            'lon' => -46.6333,
        ]]),
    ]);

    $location = geocodingProvider()->reverse(new Coordinates(-23.5505, -46.6333));

    expect($location?->toArray())->toBe([
        'name' => 'São Paulo',
        'state' => 'São Paulo',
        'country' => 'BR',
        'latitude' => -23.5505,
        'longitude' => -46.6333,
    ]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/geo/1.0/reverse?')
        && str_contains($request->url(), 'lat=-23.5505')
        && str_contains($request->url(), 'lon=-46.6333')
        && str_contains($request->url(), 'limit=1')
    );
});

it('returns null when reverse geocoding finds no location', function () {
    Http::fake(['*' => Http::response([])]);

    expect(geocodingProvider()->reverse(new Coordinates(0, 0)))->toBeNull();
});

it('rejects malformed reverse geocoding payloads', function (array $payload) {
    Http::fake(['*' => Http::response($payload)]);

    expect(fn () => geocodingProvider()->reverse(new Coordinates(51.5, -0.12)))
        ->toThrow(WeatherProviderException::class, 'invalid response');
})->with([
    'not a list' => [['name' => 'London']],
    'more than one result' => [
        ['name' => 'London', 'country' => 'GB', 'lat' => 51.5, 'lon' => -0.12],
        ['name' => 'London', 'country' => 'CA', 'lat' => 42.98, 'lon' => -81.25],
    ],
    'malformed location' => [[['name' => 'London', 'country' => 'GB']]],
]);

it('rejects malformed geocoding payloads', function (array $payload) {
    Http::fake(['*' => Http::response($payload)]);

    expect(fn () => geocodingProvider()->search('London'))
        ->toThrow(WeatherProviderException::class, 'invalid response');
})->with([
    'missing coordinates' => [[['name' => 'London', 'country' => 'GB']]],
    'missing country' => [[['name' => 'London', 'lat' => 51.5, 'lon' => -0.12]]],
    'not a list of locations' => [['cod' => 200]],
]);
