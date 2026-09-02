<?php

namespace App\Actions\Weather;

use App\DTOs\Location\LocationData;
use App\DTOs\Weather\CityComparisonData;
use App\DTOs\Weather\ComparedCityData;
use App\DTOs\Weather\WeatherDashboardData;
use App\Services\Weather\OutdoorScoreCalculator;

final readonly class CompareCitiesAction
{
    public function __construct(
        private GetWeatherDashboardAction $dashboard,
        private OutdoorScoreCalculator $scoreCalculator,
    ) {}

    public function execute(
        LocationData $left,
        LocationData $right,
    ): CityComparisonData {
        $leftCity = $this->compare($this->dashboard->execute($left));
        $rightCity = $this->compare($this->dashboard->execute($right));

        return new CityComparisonData(
            left: $leftCity,
            right: $rightCity,
            recommendation: match (true) {
                $leftCity->outdoorScore > $rightCity->outdoorScore => 'left',
                $rightCity->outdoorScore > $leftCity->outdoorScore => 'right',
                default => 'tie',
            },
        );
    }

    private function compare(WeatherDashboardData $dashboard): ComparedCityData
    {
        $nextPeriod = $dashboard->hourly[0] ?? null;
        $rainProbability = $nextPeriod?->probabilityOfPrecipitation ?? 0.0;

        return new ComparedCityData(
            location: $dashboard->location,
            current: $dashboard->current,
            rainProbability: $rainProbability,
            outdoorScore: $this->scoreCalculator->calculate(
                temperature: $dashboard->current->temperature,
                rainProbability: $rainProbability,
                humidity: $dashboard->current->humidity,
                windSpeed: $dashboard->current->windSpeed,
                condition: $dashboard->current->condition,
            ),
        );
    }
}
