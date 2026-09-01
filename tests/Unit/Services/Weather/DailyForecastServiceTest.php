<?php

use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;
use App\Services\Weather\DailyForecastService;

function period(
    string $datetime,
    float $minimum,
    float $maximum,
    string $condition = 'Clear',
    float $rainProbability = 0,
): ForecastPeriodData {
    return new ForecastPeriodData(
        datetime: strtotime($datetime),
        temperature: ($minimum + $maximum) / 2,
        minTemperature: $minimum,
        maxTemperature: $maximum,
        condition: $condition,
        weatherCode: 800,
        probabilityOfPrecipitation: $rainProbability,
        windSpeed: 2.5,
    );
}

it('aggregates multiple periods from the same local day', function () {
    $forecast = new ForecastData([
        period('2026-09-01 03:00:00 UTC', 17, 20, 'Clear', 0.05),
        period('2026-09-01 12:00:00 UTC', 19, 28, 'Clear', 0.1),
        period('2026-09-01 21:00:00 UTC', 18, 24, 'Clouds', 0.2),
    ], timezoneOffset: -10_800);

    $daily = (new DailyForecastService)->aggregate($forecast);

    expect($daily)->toHaveCount(1)
        ->and($daily[0]->toArray())->toBe([
            'date' => '2026-09-01',
            'minTemperature' => 17.0,
            'maxTemperature' => 28.0,
            'dominantCondition' => 'Clear',
            'maxRainProbability' => 0.2,
        ]);
});

it('uses the location timezone when periods cross a local day boundary', function () {
    $forecast = new ForecastData([
        period('2026-09-02 01:00:00 UTC', 18, 21),
        period('2026-09-02 04:00:00 UTC', 17, 20),
    ], timezoneOffset: -10_800);

    $daily = (new DailyForecastService)->aggregate($forecast);

    expect($daily)->toHaveCount(2)
        ->and($daily[0]->date)->toBe('2026-09-01')
        ->and($daily[1]->date)->toBe('2026-09-02');
});

it('selects rain when it is the dominant condition', function () {
    $forecast = new ForecastData([
        period('2026-09-01 09:00:00 UTC', 18, 22, 'Clouds'),
        period('2026-09-01 12:00:00 UTC', 19, 23, 'Rain', 0.7),
        period('2026-09-01 15:00:00 UTC', 18, 21, 'Rain', 0.9),
    ], timezoneOffset: 0);

    $daily = (new DailyForecastService)->aggregate($forecast);

    expect($daily[0]->dominantCondition)->toBe('Rain')
        ->and($daily[0]->maxRainProbability)->toBe(0.9);
});

it('breaks condition frequency ties deterministically', function () {
    $forecast = new ForecastData([
        period('2026-09-01 09:00:00 UTC', 18, 22, 'Clear'),
        period('2026-09-01 12:00:00 UTC', 19, 23, 'Rain'),
    ], timezoneOffset: 0);

    expect((new DailyForecastService)->aggregate($forecast)[0]->dominantCondition)
        ->toBe('Rain');
});

it('returns no days for an empty forecast', function () {
    expect((new DailyForecastService)->aggregate(new ForecastData([], 0)))->toBe([]);
});
