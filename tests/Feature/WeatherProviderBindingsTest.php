<?php

use App\Contracts\Cache\WeatherCache;
use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;
use App\Integrations\OpenWeather\OpenWeatherClient;
use App\Integrations\OpenWeather\OpenWeatherProvider;
use App\Integrations\Redis\RedisWeatherCache;

it('resolves weather contracts to the configured provider singleton', function () {
    $geocoding = app(GeocodingProvider::class);

    expect($geocoding)->toBeInstanceOf(OpenWeatherProvider::class)
        ->and(app(CurrentWeatherProvider::class))->toBe($geocoding)
        ->and(app(ForecastProvider::class))->toBe($geocoding);
});

it('configures the OpenWeather client from weather configuration', function () {
    expect(app(OpenWeatherClient::class)->baseUrl())
        ->toBe(config('weather.providers.openweather.base_url'));
});

it('injects the configured Redis cache implementation through its contract', function () {
    expect(app(WeatherCache::class))->toBeInstanceOf(RedisWeatherCache::class);
});
