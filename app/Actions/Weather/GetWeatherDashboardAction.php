<?php

namespace App\Actions\Weather;

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\DTOs\Location\LocationData;
use App\DTOs\Weather\WeatherDashboardData;
use App\Services\Weather\DailyForecastService;
use App\Services\Weather\WeatherThemeResolver;

final readonly class GetWeatherDashboardAction
{
    public function __construct(
        private CurrentWeatherProvider $currentWeather,
        private ForecastProvider $forecast,
        private DailyForecastService $dailyForecast,
        private WeatherThemeResolver $themeResolver,
    ) {}

    public function execute(LocationData $location, bool $forceRefresh = false): WeatherDashboardData
    {
        $current = $this->currentWeather->current($location->coordinates, $forceRefresh);
        $forecast = $this->forecast->forecast($location->coordinates, $forceRefresh);

        return new WeatherDashboardData(
            location: $location,
            current: $current,
            hourly: $forecast->periods,
            daily: $this->dailyForecast->aggregate($forecast),
            timezoneOffset: $forecast->timezoneOffset,
            theme: $this->themeResolver->resolve(
                condition: $current->condition,
                sunrise: $current->sunrise,
                sunset: $current->sunset,
                timestamp: $current->timestamp,
            ),
        );
    }
}
