<?php

namespace App\DTOs\Weather;

final readonly class ForecastPeriodData
{
    public function __construct(
        public int $datetime,
        public float $temperature,
        public float $minTemperature,
        public float $maxTemperature,
        public string $condition,
        public int $weatherCode,
        public float $probabilityOfPrecipitation,
        public float $windSpeed,
    ) {}

    /** @return array<string, float|int|string> */
    public function toArray(): array
    {
        return [
            'datetime' => $this->datetime,
            'temperature' => $this->temperature,
            'minTemperature' => $this->minTemperature,
            'maxTemperature' => $this->maxTemperature,
            'condition' => $this->condition,
            'weatherCode' => $this->weatherCode,
            'probabilityOfPrecipitation' => $this->probabilityOfPrecipitation,
            'windSpeed' => $this->windSpeed,
        ];
    }
}
