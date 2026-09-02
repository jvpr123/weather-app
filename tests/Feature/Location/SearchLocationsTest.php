<?php

use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\Exceptions\WeatherProviderException;

function fakeGeocodingProvider(array $locations = []): GeocodingProvider
{
    return new class($locations) implements GeocodingProvider
    {
        /** @var list<array{query: string, limit: int}> */
        public array $searches = [];

        /** @param list<LocationData> $locations */
        public function __construct(private readonly array $locations) {}

        public function search(string $query, int $limit = 5): array
        {
            $this->searches[] = compact('query', 'limit');

            return $this->locations;
        }

        public function reverse(Coordinates $coordinates): ?LocationData
        {
            return null;
        }
    };
}

it('returns normalized locations through the provider contract', function () {
    $provider = fakeGeocodingProvider([
        new LocationData(
            name: 'São Paulo',
            state: 'São Paulo',
            country: 'BR',
            coordinates: new Coordinates(-23.5505, -46.6333),
        ),
    ]);
    app()->instance(GeocodingProvider::class, $provider);

    $this->getJson('/locations/search?q=%20São%20Paulo%20')
        ->assertOk()
        ->assertExactJson([
            'data' => [[
                'name' => 'São Paulo',
                'state' => 'São Paulo',
                'country' => 'BR',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
            ]],
        ]);

    expect($provider->searches)->toBe([
        ['query' => 'São Paulo', 'limit' => 5],
    ]);
});

it('validates the search query', function (?string $query) {
    app()->instance(GeocodingProvider::class, fakeGeocodingProvider());

    $parameters = $query === null ? [] : ['q' => $query];

    $this->getJson('/locations/search?'.http_build_query($parameters))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
})->with([
    'missing' => null,
    'too short' => 'a',
    'blank after trimming' => '   ',
    'too long' => str_repeat('a', 101),
]);

it('returns an empty normalized collection', function () {
    app()->instance(GeocodingProvider::class, fakeGeocodingProvider());

    $this->getJson('/locations/search?q=Nowhere')
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not expose provider failures in the public response', function () {
    app()->instance(GeocodingProvider::class, new class implements GeocodingProvider
    {
        public function search(string $query, int $limit = 5): array
        {
            throw WeatherProviderException::unavailable(500);
        }

        public function reverse(Coordinates $coordinates): ?LocationData
        {
            return null;
        }
    });

    $response = $this->getJson('/locations/search?q=London')
        ->assertServiceUnavailable()
        ->assertExactJson([
            'code' => 'weather_unavailable',
            'message' => 'Não foi possível atualizar o clima agora.',
        ]);

    expect($response->getContent())->not->toContain('OpenWeather')
        ->not->toContain('temporarily unavailable');
});
