<?php

namespace App\Services\Weather;

final readonly class OutdoorScoreCalculator
{
    private const TEMPERATURE_WEIGHT = 0.30;

    private const RAIN_WEIGHT = 0.30;

    private const HUMIDITY_WEIGHT = 0.15;

    private const WIND_WEIGHT = 0.15;

    private const CONDITION_WEIGHT = 0.10;

    /** @var array<string, float> */
    private const CONDITION_SCORES = [
        'Clear' => 10.0,
        'Clouds' => 8.0,
        'Drizzle' => 5.0,
        'Rain' => 3.0,
        'Thunderstorm' => 1.0,
    ];

    public function calculate(
        float $temperature,
        float $rainProbability,
        int $humidity,
        float $windSpeed,
        string $condition,
    ): float {
        $score = ($this->temperatureScore($temperature) * self::TEMPERATURE_WEIGHT)
            + ($this->rainScore($rainProbability) * self::RAIN_WEIGHT)
            + ($this->humidityScore($humidity) * self::HUMIDITY_WEIGHT)
            + ($this->windScore($windSpeed) * self::WIND_WEIGHT)
            + ($this->conditionScore($condition) * self::CONDITION_WEIGHT);

        return round($this->clamp($score), 1);
    }

    private function temperatureScore(float $temperature): float
    {
        $distance = match (true) {
            $temperature < 18 => 18 - $temperature,
            $temperature > 26 => $temperature - 26,
            default => 0.0,
        };

        return $this->clamp(10 - ($distance * 1.25));
    }

    private function rainScore(float $probability): float
    {
        return (1 - max(0.0, min(1.0, $probability))) * 10;
    }

    private function humidityScore(int $humidity): float
    {
        $distance = match (true) {
            $humidity < 40 => 40 - $humidity,
            $humidity > 70 => $humidity - 70,
            default => 0,
        };

        return $this->clamp(10 - ($distance / 3));
    }

    private function windScore(float $speed): float
    {
        if ($speed <= 5) {
            return 10.0;
        }

        return $this->clamp(10 - (($speed - 5) * 1.5));
    }

    private function conditionScore(string $condition): float
    {
        return self::CONDITION_SCORES[$condition] ?? 4.0;
    }

    private function clamp(float $score): float
    {
        return max(0.0, min(10.0, $score));
    }
}
