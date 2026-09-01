<?php

namespace App\Providers;

use App\Contracts\Cache\WeatherCache;
use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;
use App\Integrations\OpenWeather\OpenWeatherClient;
use App\Integrations\Redis\RedisWeatherCache;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LogicException;

final class WeatherServiceProvider extends ServiceProvider
{
    /** @var list<class-string> */
    private const CONTRACTS = [
        GeocodingProvider::class,
        CurrentWeatherProvider::class,
        ForecastProvider::class,
    ];

    public function register(): void
    {
        $provider = $this->providerName();
        $configuration = $this->providerConfiguration($provider);
        $driver = $this->driver($provider, $configuration);

        $this->app->singleton(OpenWeatherClient::class, fn (): OpenWeatherClient => new OpenWeatherClient(
            apiKey: (string) ($configuration['api_key'] ?? ''),
            baseUrl: (string) ($configuration['base_url'] ?? ''),
            timeout: (int) ($configuration['timeout'] ?? 10),
            connectTimeout: (int) ($configuration['connect_timeout'] ?? 5),
            retryTimes: (int) ($configuration['retry_times'] ?? 3),
            retryDelayMilliseconds: (int) ($configuration['retry_delay_ms'] ?? 200),
        ));

        $this->app->singleton(WeatherCache::class, function (Application $app): WeatherCache {
            $store = $app['config']->get('weather.cache.store', 'redis');

            if (! is_string($store) || trim($store) === '') {
                throw new LogicException('The configured weather cache store must be a non-empty string.');
            }

            return new RedisWeatherCache(
                $app->make(CacheFactory::class)->store($store),
            );
        });

        $this->app->when($driver)
            ->needs('$geocodingTtl')
            ->give(fn (): int => max(1, (int) $this->app['config']->get('weather.cache.geocoding_ttl', 1800)));
        $this->app->when($driver)
            ->needs('$currentWeatherTtl')
            ->give(fn (): int => max(1, (int) $this->app['config']->get('weather.cache.current_ttl', 600)));
        $this->app->when($driver)
            ->needs('$forecastTtl')
            ->give(fn (): int => max(1, (int) $this->app['config']->get('weather.cache.forecast_ttl', 1800)));

        $this->app->singleton($driver);

        foreach (self::CONTRACTS as $contract) {
            $this->app->singleton($contract, fn (Application $app): object => $app->make($driver));
        }
    }

    private function providerName(): string
    {
        $provider = $this->app['config']->get('weather.provider');

        if (! is_string($provider) || trim($provider) === '') {
            throw new LogicException('The configured weather provider must be a non-empty string.');
        }

        return $provider;
    }

    /** @return array<string, mixed> */
    private function providerConfiguration(string $provider): array
    {
        $configuration = $this->app['config']->get("weather.providers.{$provider}");

        if (! is_array($configuration)) {
            throw new LogicException("Weather provider [{$provider}] is not configured.");
        }

        return $configuration;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return class-string
     */
    private function driver(string $provider, array $configuration): string
    {
        $driver = $configuration['driver'] ?? null;

        if (! is_string($driver) || ! class_exists($driver)) {
            throw new LogicException("Weather provider [{$provider}] has an invalid driver.");
        }

        foreach (self::CONTRACTS as $contract) {
            if (! is_a($driver, $contract, true)) {
                throw new LogicException("Weather provider driver [{$driver}] must implement [{$contract}].");
            }
        }

        return $driver;
    }
}
