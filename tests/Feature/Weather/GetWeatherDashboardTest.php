<?php

use App\Contracts\Weather\CurrentWeatherProvider;
use App\Contracts\Weather\ForecastProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\CurrentWeatherData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use App\Exceptions\WeatherProviderException;

function bindDashboardWeatherProviders(): void
{
    app()->instance(CurrentWeatherProvider::class, new class implements CurrentWeatherProvider
    {
        public function current(Coordinates $coordinates): CurrentWeatherData
        {
            return new CurrentWeatherData(
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
        }
    });

    app()->instance(ForecastProvider::class, new class implements ForecastProvider
    {
        public function forecast(Coordinates $coordinates): ForecastData
        {
            return new ForecastData([
                new ForecastPeriodData(
                    datetime: 1_777_636_800,
                    temperature: 28.4,
                    minTemperature: 25.2,
                    maxTemperature: 31.1,
                    condition: 'Clear',
                    weatherCode: 800,
                    isDaytime: true,
                    probabilityOfPrecipitation: 0.1,
                    windSpeed: 3.4,
                ),
            ], timezoneOffset: -10_800);
        }
    });
}

function dashboardQuery(array $overrides = []): string
{
    return http_build_query(array_merge([
        'name' => 'São Paulo',
        'state' => 'São Paulo',
        'country' => 'BR',
        'latitude' => -23.5505,
        'longitude' => -46.6333,
    ], $overrides));
}

it('returns the normalized weather dashboard contract', function () {
    bindDashboardWeatherProviders();

    $this->getJson('/weather/dashboard?'.dashboardQuery([
        'name' => ' São Paulo ',
        'country' => 'br',
    ]))
        ->assertOk()
        ->assertJsonPath('data.location.name', 'São Paulo')
        ->assertJsonPath('data.location.country', 'BR')
        ->assertJsonPath('data.current.temperature', 28.4)
        ->assertJsonPath('data.current.condition', 'Clear')
        ->assertJsonPath('data.hourly.0.datetime', 1_777_636_800)
        ->assertJsonPath('data.daily.0.minTemperature', 25.2)
        ->assertJsonPath('data.daily.0.maxTemperature', 31.1)
        ->assertJsonPath('data.timezoneOffset', -10_800)
        ->assertJsonPath('data.theme', 'clear-day')
        ->assertJsonStructure([
            'data' => [
                'location',
                'current',
                'hourly',
                'daily',
                'timezoneOffset',
                'theme',
            ],
        ]);
});

it('accepts a location without a state', function () {
    bindDashboardWeatherProviders();

    $this->getJson('/weather/dashboard?'.dashboardQuery(['state' => '']))
        ->assertOk()
        ->assertJsonPath('data.location.state', null);
});

it('validates dashboard location input', function (array $overrides, array $errors) {
    bindDashboardWeatherProviders();

    $this->getJson('/weather/dashboard?'.dashboardQuery($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'blank name' => [['name' => '  '], ['name']],
    'invalid country' => [['country' => 'BRA'], ['country']],
    'invalid latitude' => [['latitude' => 91], ['latitude']],
    'invalid longitude' => [['longitude' => -181], ['longitude']],
]);

it('returns sanitized weather error responses', function (
    WeatherProviderException $exception,
    int $status,
    string $code,
    string $message,
) {
    app()->instance(CurrentWeatherProvider::class, new class($exception) implements CurrentWeatherProvider
    {
        public function __construct(private readonly WeatherProviderException $exception) {}

        public function current(Coordinates $coordinates): CurrentWeatherData
        {
            throw $this->exception;
        }
    });

    $response = $this->getJson('/weather/dashboard?'.dashboardQuery())
        ->assertStatus($status)
        ->assertExactJson(compact('code', 'message'));

    expect($response->getContent())
        ->not->toContain('OpenWeather')
        ->not->toContain('authentication')
        ->not->toContain('temporarily unavailable');
})->with([
    'not found' => [WeatherProviderException::notFound(), 404, 'weather_not_found', 'Cidade não encontrada.'],
    'rate limited' => [WeatherProviderException::rateLimited(), 429, 'weather_rate_limited', 'O serviço de clima está ocupado. Tente novamente em instantes.'],
    'timeout' => [WeatherProviderException::timeout(), 504, 'weather_timeout', 'A consulta do clima demorou mais que o esperado.'],
    'network error' => [WeatherProviderException::network(), 503, 'weather_network_error', 'Não foi possível conectar ao serviço de clima.'],
    'unavailable' => [WeatherProviderException::unavailable(500), 503, 'weather_unavailable', 'Não foi possível atualizar o clima agora.'],
]);
