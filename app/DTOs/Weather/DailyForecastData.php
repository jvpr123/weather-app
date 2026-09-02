<?php

namespace App\DTOs\Weather;

final readonly class DailyForecastData
{
    public function __construct(
        public string $date,
        public float $minTemperature,
        public float $maxTemperature,
        public string $dominantCondition,
        public float $maxRainProbability,
    ) {}

    /** @return array<string, float|string> */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'minTemperature' => $this->minTemperature,
            'maxTemperature' => $this->maxTemperature,
            'dominantCondition' => $this->dominantCondition,
            'maxRainProbability' => $this->maxRainProbability,
        ];
    }
}
