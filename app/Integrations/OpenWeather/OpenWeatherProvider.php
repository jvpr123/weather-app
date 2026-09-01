<?php

namespace App\Integrations\OpenWeather;

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\Contracts\Weather\GeocodingProvider;

final readonly class OpenWeatherProvider implements CurrentWeatherProvider, ForecastProvider, GeocodingProvider
{
    public function __construct(
        private OpenWeatherClient $client,
    ) {}
}
