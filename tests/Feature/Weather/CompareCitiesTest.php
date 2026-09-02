<?php

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\CurrentWeatherData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use Inertia\Testing\AssertableInertia as Assert;

function comparisonEndpointQuery(array $overrides = []): string
{
    return http_build_query(array_replace_recursive([
        'left' => [
            'name' => 'São Paulo',
            'state' => 'São Paulo',
            'country' => 'BR',
            'latitude' => 1,
            'longitude' => 1,
        ],
        'right' => [
            'name' => 'Curitiba',
            'state' => 'Paraná',
            'country' => 'BR',
            'latitude' => 2,
            'longitude' => 2,
        ],
    ], $overrides));
}

function bindComparisonEndpointProviders(): void
{
    app()->instance(CurrentWeatherProvider::class, new class implements CurrentWeatherProvider
    {
        public function current(Coordinates $coordinates): CurrentWeatherData
        {
            $isLeft = $coordinates->latitude === 1.0;

            return new CurrentWeatherData(
                temperature: $isLeft ? 22 : 35,
                feelsLike: $isLeft ? 22 : 38,
                minTemperature: $isLeft ? 20 : 30,
                maxTemperature: $isLeft ? 24 : 38,
                humidity: $isLeft ? 55 : 90,
                pressure: 1015,
                windSpeed: $isLeft ? 2 : 15,
                weatherCode: $isLeft ? 800 : 211,
                condition: $isLeft ? 'Clear' : 'Thunderstorm',
                description: $isLeft ? 'céu limpo' : 'tempestade',
                icon: $isLeft ? '01d' : '11d',
                sunrise: 1_777_617_600,
                sunset: 1_777_660_800,
                timestamp: 1_777_636_800,
            );
        }
    });

    app()->instance(ForecastProvider::class, new class implements ForecastProvider
    {
        public function forecast(Coordinates $coordinates): ForecastData
        {
            $isLeft = $coordinates->latitude === 1.0;

            return new ForecastData([
                new ForecastPeriodData(
                    datetime: 1_777_636_800,
                    temperature: $isLeft ? 22 : 35,
                    minTemperature: $isLeft ? 20 : 30,
                    maxTemperature: $isLeft ? 24 : 38,
                    condition: $isLeft ? 'Clear' : 'Thunderstorm',
                    weatherCode: $isLeft ? 800 : 211,
                    isDaytime: true,
                    probabilityOfPrecipitation: $isLeft ? 0 : 0.95,
                    windSpeed: $isLeft ? 2 : 15,
                ),
            ], timezoneOffset: -10_800);
        }
    });
}

it('renders the city comparison page', function () {
    $this->get('/weather/compare')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Weather/Compare')
        );
});

it('returns a normalized city comparison', function () {
    bindComparisonEndpointProviders();

    $this->getJson('/weather/compare/results?'.comparisonEndpointQuery([
        'left' => ['name' => ' São Paulo ', 'country' => 'br'],
    ]))
        ->assertOk()
        ->assertJsonPath('data.left.location.name', 'São Paulo')
        ->assertJsonPath('data.left.location.country', 'BR')
        ->assertJsonPath('data.left.outdoorScore', 10)
        ->assertJsonPath('data.right.location.name', 'Curitiba')
        ->assertJsonPath('data.right.outdoorScore', 0.8)
        ->assertJsonPath('data.recommendation', 'left')
        ->assertJsonStructure([
            'data' => [
                'left' => ['location', 'current', 'rainProbability', 'outdoorScore'],
                'right' => ['location', 'current', 'rainProbability', 'outdoorScore'],
                'recommendation',
            ],
        ]);
});

it('validates both comparison locations', function (array $overrides, array $errors) {
    bindComparisonEndpointProviders();

    $this->getJson('/weather/compare/results?'.comparisonEndpointQuery($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'blank left name' => [['left' => ['name' => '  ']], ['left.name']],
    'invalid left latitude' => [['left' => ['latitude' => -91]], ['left.latitude']],
    'invalid right country' => [['right' => ['country' => 'BRA']], ['right.country']],
    'invalid right longitude' => [['right' => ['longitude' => 181]], ['right.longitude']],
]);
