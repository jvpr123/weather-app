<?php

namespace App\DTOs\Weather;

final readonly class CurrentWeatherData
{
    public function __construct(
        public float $temperature,
        public float $feelsLike,
        public float $minTemperature,
        public float $maxTemperature,
        public int $humidity,
        public int $pressure,
        public float $windSpeed,
        public int $weatherCode,
        public string $condition,
        public string $description,
        public string $icon,
        public int $sunrise,
        public int $sunset,
        public int $timestamp,
    ) {}

    /** @return array<string, float|int|string> */
    public function toArray(): array
    {
        return [
            'temperature' => $this->temperature,
            'feelsLike' => $this->feelsLike,
            'minTemperature' => $this->minTemperature,
            'maxTemperature' => $this->maxTemperature,
            'humidity' => $this->humidity,
            'pressure' => $this->pressure,
            'windSpeed' => $this->windSpeed,
            'weatherCode' => $this->weatherCode,
            'condition' => $this->condition,
            'description' => $this->description,
            'icon' => $this->icon,
            'sunrise' => $this->sunrise,
            'sunset' => $this->sunset,
            'timestamp' => $this->timestamp,
        ];
    }
}
