<?php

namespace App\Integrations\OpenWeather;

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\Exceptions\WeatherProviderException;

final readonly class OpenWeatherProvider implements CurrentWeatherProvider, ForecastProvider, GeocodingProvider
{
    public function __construct(
        private OpenWeatherClient $client,
    ) {}

    /** @return list<LocationData> */
    public function search(string $query, int $limit = 5): array
    {
        $payload = $this->client->get('/geo/1.0/direct', [
            'q' => trim($query),
            'limit' => max(1, min($limit, 5)),
        ]);

        return array_map(
            fn (mixed $location): LocationData => $this->mapLocation($location),
            array_values($payload),
        );
    }

    private function mapLocation(mixed $location): LocationData
    {
        if (! is_array($location)
            || ! is_string($location['name'] ?? null)
            || trim($location['name']) === ''
            || ! is_string($location['country'] ?? null)
            || trim($location['country']) === ''
            || ! is_numeric($location['lat'] ?? null)
            || ! is_numeric($location['lon'] ?? null)
            || (isset($location['state']) && ! is_string($location['state']))) {
            throw WeatherProviderException::invalidResponse();
        }

        return new LocationData(
            name: trim($location['name']),
            state: isset($location['state']) ? trim($location['state']) : null,
            country: strtoupper(trim($location['country'])),
            coordinates: new Coordinates(
                latitude: (float) $location['lat'],
                longitude: (float) $location['lon'],
            ),
        );
    }
}
