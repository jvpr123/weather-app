<?php

use App\Actions\Weather\GetWeatherDashboardAction;
use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\DTOs\Weather\CurrentWeatherData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use App\Services\Weather\DailyForecastService;
use App\Services\Weather\WeatherThemeResolver;

it('orchestrates providers and domain services into a dashboard DTO', function () {
    $coordinates = new Coordinates(-23.5505, -46.6333);
    $location = new LocationData('São Paulo', 'São Paulo', 'BR', $coordinates);
    $current = new CurrentWeatherData(
        temperature: 28.4,
        feelsLike: 29.7,
        minTemperature: 25.2,
        maxTemperature: 31.1,
        humidity: 55,
        pressure: 1015,
        windSpeed: 3.4,
        weatherCode: 800,
        condition: 'Clear',
        description: 'céu limpo',
        icon: '01d',
        sunrise: 1_777_617_600,
        sunset: 1_777_660_800,
        timestamp: 1_777_636_800,
    );
    $period = new ForecastPeriodData(
        datetime: 1_777_636_800,
        temperature: 28.4,
        minTemperature: 25.2,
        maxTemperature: 31.1,
        condition: 'Clear',
        weatherCode: 800,
        isDaytime: true,
        probabilityOfPrecipitation: 0.1,
        windSpeed: 3.4,
    );
    $currentProvider = new class($current) implements CurrentWeatherProvider
    {
        public ?Coordinates $receivedCoordinates = null;

        public function __construct(private readonly CurrentWeatherData $weather) {}

        public function current(Coordinates $coordinates): CurrentWeatherData
        {
            $this->receivedCoordinates = $coordinates;

            return $this->weather;
        }
    };
    $forecastProvider = new class($period) implements ForecastProvider
    {
        public ?Coordinates $receivedCoordinates = null;

        public function __construct(private readonly ForecastPeriodData $period) {}

        public function forecast(Coordinates $coordinates): ForecastData
        {
            $this->receivedCoordinates = $coordinates;

            return new ForecastData([$this->period], timezoneOffset: -10_800);
        }
    };
    $action = new GetWeatherDashboardAction(
        currentWeather: $currentProvider,
        forecast: $forecastProvider,
        dailyForecast: new DailyForecastService,
        themeResolver: new WeatherThemeResolver,
    );

    $dashboard = $action->execute($location);

    expect($currentProvider->receivedCoordinates)->toBe($coordinates)
        ->and($forecastProvider->receivedCoordinates)->toBe($coordinates)
        ->and($dashboard->location)->toBe($location)
        ->and($dashboard->current)->toBe($current)
        ->and($dashboard->hourly)->toBe([$period])
        ->and($dashboard->daily)->toHaveCount(1)
        ->and($dashboard->theme)->toBe('clear-day')
        ->and($dashboard->toArray())->toHaveKeys([
            'location',
            'current',
            'hourly',
            'daily',
            'theme',
        ]);
});
