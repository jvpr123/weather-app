<?php

namespace App\Integrations\OpenWeather;

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\DTOs\Weather\CurrentWeatherData;
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

    public function reverse(Coordinates $coordinates): ?LocationData
    {
        $payload = $this->client->get('/geo/1.0/reverse', [
            'lat' => $coordinates->latitude,
            'lon' => $coordinates->longitude,
            'limit' => 1,
        ]);

        if ($payload === []) {
            return null;
        }

        if (! array_is_list($payload) || count($payload) !== 1) {
            throw WeatherProviderException::invalidResponse();
        }

        return $this->mapLocation($payload[0]);
    }

    public function current(Coordinates $coordinates): CurrentWeatherData
    {
        $payload = $this->client->get('/data/2.5/weather', [
            'lat' => $coordinates->latitude,
            'lon' => $coordinates->longitude,
            'units' => 'metric',
            'lang' => 'pt_br',
        ]);

        return $this->mapCurrentWeather($payload);
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

    /** @param array<array-key, mixed> $payload */
    private function mapCurrentWeather(array $payload): CurrentWeatherData
    {
        $main = $payload['main'] ?? null;
        $wind = $payload['wind'] ?? null;
        $system = $payload['sys'] ?? null;
        $weather = $payload['weather'] ?? null;
        $condition = is_array($weather) && array_is_list($weather)
            ? ($weather[0] ?? null)
            : null;

        if (! is_array($main)
            || ! is_array($wind)
            || ! is_array($system)
            || ! is_array($condition)
            || ! $this->hasNumericValues($main, ['temp', 'feels_like', 'temp_min', 'temp_max', 'humidity', 'pressure'])
            || ! $this->hasNumericValues($wind, ['speed'])
            || ! $this->hasNumericValues($system, ['sunrise', 'sunset'])
            || ! $this->hasNumericValues($payload, ['dt'])
            || ! is_numeric($condition['id'] ?? null)
            || ! is_string($condition['main'] ?? null)
            || trim($condition['main']) === ''
            || ! is_string($condition['description'] ?? null)
            || trim($condition['description']) === ''
            || ! is_string($condition['icon'] ?? null)
            || trim($condition['icon']) === '') {
            throw WeatherProviderException::invalidResponse();
        }

        return new CurrentWeatherData(
            temperature: (float) $main['temp'],
            feelsLike: (float) $main['feels_like'],
            minTemperature: (float) $main['temp_min'],
            maxTemperature: (float) $main['temp_max'],
            humidity: (int) $main['humidity'],
            pressure: (int) $main['pressure'],
            windSpeed: (float) $wind['speed'],
            weatherCode: (int) $condition['id'],
            condition: trim($condition['main']),
            description: trim($condition['description']),
            icon: trim($condition['icon']),
            sunrise: (int) $system['sunrise'],
            sunset: (int) $system['sunset'],
            timestamp: (int) $payload['dt'],
        );
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function hasNumericValues(array $payload, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! is_numeric($payload[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
