<?php

namespace App\Services\Weather;

use App\DTOs\Weather\DailyForecastData;
use App\DTOs\Weather\ForecastData;
use App\DTOs\Weather\ForecastPeriodData;

final readonly class DailyForecastService
{
    /** @var array<string, int> */
    private const CONDITION_PRIORITY = [
        'Thunderstorm' => 70,
        'Rain' => 60,
        'Drizzle' => 50,
        'Snow' => 40,
        'Clouds' => 30,
        'Clear' => 20,
    ];

    /** @return list<DailyForecastData> */
    public function aggregate(ForecastData $forecast): array
    {
        /** @var array<string, list<ForecastPeriodData>> $periodsByDate */
        $periodsByDate = [];

        foreach ($forecast->periods as $period) {
            $localDate = gmdate('Y-m-d', $period->datetime + $forecast->timezoneOffset);
            $periodsByDate[$localDate][] = $period;
        }

        ksort($periodsByDate);

        return array_values(array_map(
            fn (array $periods, string $date): DailyForecastData => $this->aggregateDay($date, $periods),
            $periodsByDate,
            array_keys($periodsByDate),
        ));
    }

    /** @param list<ForecastPeriodData> $periods */
    private function aggregateDay(string $date, array $periods): DailyForecastData
    {
        $minimums = array_map(
            fn (ForecastPeriodData $period): float => $period->minTemperature,
            $periods,
        );
        $maximums = array_map(
            fn (ForecastPeriodData $period): float => $period->maxTemperature,
            $periods,
        );
        $rainProbabilities = array_map(
            fn (ForecastPeriodData $period): float => $period->probabilityOfPrecipitation,
            $periods,
        );

        return new DailyForecastData(
            date: $date,
            minTemperature: min($minimums),
            maxTemperature: max($maximums),
            dominantCondition: $this->dominantCondition($periods),
            maxRainProbability: max($rainProbabilities),
        );
    }

    /** @param list<ForecastPeriodData> $periods */
    private function dominantCondition(array $periods): string
    {
        $counts = [];

        foreach ($periods as $period) {
            $counts[$period->condition] = ($counts[$period->condition] ?? 0) + 1;
        }

        $conditions = array_keys($counts);

        usort($conditions, function (string $left, string $right) use ($counts): int {
            $frequency = $counts[$right] <=> $counts[$left];

            if ($frequency !== 0) {
                return $frequency;
            }

            $priority = (self::CONDITION_PRIORITY[$right] ?? 0)
                <=> (self::CONDITION_PRIORITY[$left] ?? 0);

            return $priority !== 0 ? $priority : strcmp($left, $right);
        });

        return $conditions[0];
    }
}
