<?php

use App\Actions\Weather\CompareCitiesAction;
use App\Actions\Weather\GetWeatherDashboardAction;
use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\DTOs\Weather\CurrentWeatherData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use App\Services\Weather\DailyForecastService;
use App\Services\Weather\OutdoorScoreCalculator;
use App\Services\Weather\WeatherThemeResolver;

function comparisonWeather(
    float $temperature,
    int $humidity,
    float $windSpeed,
    string $condition,
): CurrentWeatherData {
    return new CurrentWeatherData(
        temperature: $temperature,
        feelsLike: $temperature,
        minTemperature: $temperature - 2,
        maxTemperature: $temperature + 2,
        humidity: $humidity,
        pressure: 1015,
        windSpeed: $windSpeed,
        weatherCode: $condition === 'Clear' ? 800 : 211,
        condition: $condition,
        description: $condition,
        icon: $condition === 'Clear' ? '01d' : '11d',
        sunrise: 1_777_617_600,
        sunset: 1_777_660_800,
        timestamp: 1_777_636_800,
    );
}

function comparisonForecast(float $rainProbability, string $condition): ForecastData
{
    return new ForecastData([
        new ForecastPeriodData(
            datetime: 1_777_636_800,
            temperature: 22,
            minTemperature: 20,
            maxTemperature: 24,
            condition: $condition,
            weatherCode: $condition === 'Clear' ? 800 : 211,
            isDaytime: true,
            probabilityOfPrecipitation: $rainProbability,
            windSpeed: 2,
        ),
    ], timezoneOffset: -10_800);
}

it('compares two cities by reusing the dashboard action', function () {
    $leftCoordinates = new Coordinates(1, 1);
    $rightCoordinates = new Coordinates(2, 2);
    $currentProvider = new class($leftCoordinates) implements CurrentWeatherProvider
    {
        /** @var list<Coordinates> */
        public array $calls = [];

        public function __construct(private readonly Coordinates $leftCoordinates) {}

        public function current(Coordinates $coordinates, bool $forceRefresh = false): CurrentWeatherData
        {
            $this->calls[] = $coordinates;

            return $coordinates === $this->leftCoordinates
                ? comparisonWeather(22, 55, 2, 'Clear')
                : comparisonWeather(35, 90, 15, 'Thunderstorm');
        }
    };
    $forecastProvider = new class($leftCoordinates) implements ForecastProvider
    {
        /** @var list<Coordinates> */
        public array $calls = [];

        public function __construct(private readonly Coordinates $leftCoordinates) {}

        public function forecast(Coordinates $coordinates, bool $forceRefresh = false): ForecastData
        {
            $this->calls[] = $coordinates;

            return $coordinates === $this->leftCoordinates
                ? comparisonForecast(0, 'Clear')
                : comparisonForecast(0.95, 'Thunderstorm');
        }
    };
    $dashboard = new GetWeatherDashboardAction(
        currentWeather: $currentProvider,
        forecast: $forecastProvider,
        dailyForecast: new DailyForecastService,
        themeResolver: new WeatherThemeResolver,
    );
    $action = new CompareCitiesAction($dashboard, new OutdoorScoreCalculator);

    $comparison = $action->execute(
        new LocationData('Ideal City', null, 'BR', $leftCoordinates),
        new LocationData('Storm City', null, 'BR', $rightCoordinates),
    );

    expect($currentProvider->calls)->toBe([$leftCoordinates, $rightCoordinates])
        ->and($forecastProvider->calls)->toBe([$leftCoordinates, $rightCoordinates])
        ->and($comparison->left->outdoorScore)->toBe(10.0)
        ->and($comparison->right->outdoorScore)->toBe(0.8)
        ->and($comparison->right->rainProbability)->toBe(0.95)
        ->and($comparison->recommendation)->toBe('left')
        ->and($comparison->toArray())->toHaveKeys(['left', 'right', 'recommendation']);
});

it('recommends a tie when both cities have the same score', function () {
    $currentProvider = new class implements CurrentWeatherProvider
    {
        public function current(Coordinates $coordinates, bool $forceRefresh = false): CurrentWeatherData
        {
            return comparisonWeather(22, 55, 2, 'Clear');
        }
    };
    $forecastProvider = new class implements ForecastProvider
    {
        public function forecast(Coordinates $coordinates, bool $forceRefresh = false): ForecastData
        {
            return comparisonForecast(0, 'Clear');
        }
    };
    $action = new CompareCitiesAction(
        new GetWeatherDashboardAction(
            $currentProvider,
            $forecastProvider,
            new DailyForecastService,
            new WeatherThemeResolver,
        ),
        new OutdoorScoreCalculator,
    );

    $comparison = $action->execute(
        new LocationData('Left', null, 'BR', new Coordinates(1, 1)),
        new LocationData('Right', null, 'BR', new Coordinates(2, 2)),
    );

    expect($comparison->recommendation)->toBe('tie');
});
