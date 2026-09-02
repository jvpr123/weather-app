<?php

use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\Exceptions\WeatherProviderException;

function fakeReverseGeocodingProvider(?LocationData $location = null): GeocodingProvider
{
    return new class($location) implements GeocodingProvider
    {
        public ?Coordinates $coordinates = null;

        public function __construct(private readonly ?LocationData $location) {}

        public function search(string $query, int $limit = 5): array
        {
            return [];
        }

        public function reverse(Coordinates $coordinates): ?LocationData
        {
            $this->coordinates = $coordinates;

            return $this->location;
        }
    };
}

it('returns a normalized location for coordinates', function () {
    $provider = fakeReverseGeocodingProvider(new LocationData(
        name: 'São Paulo',
        state: 'São Paulo',
        country: 'BR',
        coordinates: new Coordinates(-23.5505, -46.6333),
    ));
    app()->instance(GeocodingProvider::class, $provider);

    $this->getJson('/locations/reverse?latitude=-23.5505&longitude=-46.6333')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'name' => 'São Paulo',
                'state' => 'São Paulo',
                'country' => 'BR',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
            ],
        ]);

    expect($provider->coordinates?->latitude)->toBe(-23.5505)
        ->and($provider->coordinates?->longitude)->toBe(-46.6333);
});

it('validates reverse geocoding coordinates', function (array $parameters, array $errors) {
    app()->instance(GeocodingProvider::class, fakeReverseGeocodingProvider());

    $this->getJson('/locations/reverse?'.http_build_query($parameters))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing' => [[], ['latitude', 'longitude']],
    'latitude too low' => [['latitude' => -91, 'longitude' => 0], ['latitude']],
    'latitude too high' => [['latitude' => 91, 'longitude' => 0], ['latitude']],
    'longitude too low' => [['latitude' => 0, 'longitude' => -181], ['longitude']],
    'longitude too high' => [['latitude' => 0, 'longitude' => 181], ['longitude']],
    'not numeric' => [['latitude' => 'north', 'longitude' => 'west'], ['latitude', 'longitude']],
]);

it('returns null when no reverse geocoding result is available', function () {
    app()->instance(GeocodingProvider::class, fakeReverseGeocodingProvider());

    $this->getJson('/locations/reverse?latitude=0&longitude=0')
        ->assertOk()
        ->assertExactJson(['data' => null]);
});

it('does not expose reverse geocoding provider failures', function () {
    app()->instance(GeocodingProvider::class, new class implements GeocodingProvider
    {
        public function search(string $query, int $limit = 5): array
        {
            return [];
        }

        public function reverse(Coordinates $coordinates): ?LocationData
        {
            throw WeatherProviderException::unavailable(500);
        }
    });

    $this->getJson('/locations/reverse?latitude=51.5&longitude=-0.12')
        ->assertServiceUnavailable()
        ->assertExactJson([
            'code' => 'weather_unavailable',
            'message' => 'Não foi possível atualizar o clima agora.',
        ]);
});
