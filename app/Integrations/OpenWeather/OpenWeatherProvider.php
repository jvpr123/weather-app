<?php

namespace App\Integrations\OpenWeather;

use App\Contracts\Cache\WeatherCache;
use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\DTOs\Weather\CurrentWeatherData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use App\Exceptions\WeatherProviderException;
use Closure;

final readonly class OpenWeatherProvider implements CurrentWeatherProvider, ForecastProvider, GeocodingProvider
{
    public function __construct(
        private OpenWeatherClient $client,
        private WeatherCache $cache,
        private int $geocodingTtl = 1800,
        private int $currentWeatherTtl = 600,
        private int $forecastTtl = 1800,
    ) {}

    /** @return list<LocationData> */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 5));
        $cacheKey = 'weather:geo:'.hash('sha256', mb_strtolower("search|{$query}|{$limit}"));
        $payload = $this->remember($cacheKey, $this->geocodingTtl, function () use ($query, $limit): array {
            $payload = $this->client->get('/geo/1.0/direct', [
                'q' => $query,
                'limit' => $limit,
            ]);

            array_map(
                fn (mixed $location): LocationData => $this->mapLocation($location),
                array_values($payload),
            );

            return $payload;
        });

        return array_map(
            fn (mixed $location): LocationData => $this->mapLocation($location),
            array_values($payload),
        );
    }

    public function reverse(Coordinates $coordinates): ?LocationData
    {
        $coordinateKey = $this->coordinateKey($coordinates);
        $cacheKey = 'weather:geo:'.hash('sha256', "reverse|{$coordinateKey}");
        $payload = $this->remember($cacheKey, $this->geocodingTtl, function () use ($coordinates): array {
            $payload = $this->client->get('/geo/1.0/reverse', [
                'lat' => $coordinates->latitude,
                'lon' => $coordinates->longitude,
                'limit' => 1,
            ]);

            $this->mapReverseLocation($payload);

            return $payload;
        });

        return $this->mapReverseLocation($payload);
    }

    /** @param array<array-key, mixed> $payload */
    private function mapReverseLocation(array $payload): ?LocationData
    {

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
        $cacheKey = 'weather:current:'.$this->coordinateKey($coordinates);
        $payload = $this->remember($cacheKey, $this->currentWeatherTtl, function () use ($coordinates): array {
            $payload = $this->client->get('/data/2.5/weather', [
                'lat' => $coordinates->latitude,
                'lon' => $coordinates->longitude,
                'units' => 'metric',
                'lang' => 'pt_br',
            ]);

            $this->mapCurrentWeather($payload);

            return $payload;
        });

        return $this->mapCurrentWeather($payload);
    }

    public function forecast(Coordinates $coordinates): ForecastData
    {
        $cacheKey = 'weather:forecast:'.$this->coordinateKey($coordinates);
        $payload = $this->remember($cacheKey, $this->forecastTtl, function () use ($coordinates): array {
            $payload = $this->client->get('/data/2.5/forecast', [
                'lat' => $coordinates->latitude,
                'lon' => $coordinates->longitude,
                'units' => 'metric',
                'lang' => 'pt_br',
            ]);

            $this->mapForecast($payload);

            return $payload;
        });

        return $this->mapForecast($payload);
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

    /** @param array<array-key, mixed> $payload */
    private function mapForecast(array $payload): ForecastData
    {
        $periods = $payload['list'] ?? null;
        $city = $payload['city'] ?? null;

        if (! is_array($periods)
            || ! array_is_list($periods)
            || ! is_array($city)
            || ! is_numeric($city['timezone'] ?? null)) {
            throw WeatherProviderException::invalidResponse();
        }

        return new ForecastData(
            periods: array_map(
                fn (mixed $period): ForecastPeriodData => $this->mapForecastPeriod($period),
                $periods,
            ),
            timezoneOffset: (int) $city['timezone'],
        );
    }

    private function mapForecastPeriod(mixed $period): ForecastPeriodData
    {
        if (! is_array($period)) {
            throw WeatherProviderException::invalidResponse();
        }

        $main = $period['main'] ?? null;
        $wind = $period['wind'] ?? null;
        $weather = $period['weather'] ?? null;
        $condition = is_array($weather) && array_is_list($weather)
            ? ($weather[0] ?? null)
            : null;

        if (! is_array($main)
            || ! is_array($wind)
            || ! is_array($condition)
            || ! $this->hasNumericValues($period, ['dt', 'pop'])
            || ! $this->hasNumericValues($main, ['temp', 'temp_min', 'temp_max'])
            || ! $this->hasNumericValues($wind, ['speed'])
            || ! is_numeric($condition['id'] ?? null)
            || ! is_string($condition['main'] ?? null)
            || trim($condition['main']) === ''
            || ! is_string($condition['icon'] ?? null)
            || ! preg_match('/^[0-9]{2}[dn]$/', $condition['icon'])) {
            throw WeatherProviderException::invalidResponse();
        }

        $probabilityOfPrecipitation = (float) $period['pop'];

        if ($probabilityOfPrecipitation < 0 || $probabilityOfPrecipitation > 1) {
            throw WeatherProviderException::invalidResponse();
        }

        return new ForecastPeriodData(
            datetime: (int) $period['dt'],
            temperature: (float) $main['temp'],
            minTemperature: (float) $main['temp_min'],
            maxTemperature: (float) $main['temp_max'],
            condition: trim($condition['main']),
            weatherCode: (int) $condition['id'],
            isDaytime: str_ends_with($condition['icon'], 'd'),
            probabilityOfPrecipitation: $probabilityOfPrecipitation,
            windSpeed: (float) $wind['speed'],
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

    /**
     * @param  Closure(): array<array-key, mixed>  $callback
     * @return array<array-key, mixed>
     */
    private function remember(string $key, int $ttl, Closure $callback): array
    {
        $payload = $this->cache->remember($key, max(1, $ttl), $callback);

        if (! is_array($payload)) {
            throw WeatherProviderException::invalidResponse();
        }

        return $payload;
    }

    private function coordinateKey(Coordinates $coordinates): string
    {
        $latitude = abs($coordinates->latitude) < 0.0000005 ? 0.0 : $coordinates->latitude;
        $longitude = abs($coordinates->longitude) < 0.0000005 ? 0.0 : $coordinates->longitude;

        return number_format($latitude, 6, '.', '').':'.number_format($longitude, 6, '.', '');
    }
}
