<?php

namespace App\DTOs\Weather;

use App\DTOs\Location\LocationData;

final readonly class ComparedCityData
{
    public function __construct(
        public LocationData $location,
        public CurrentWeatherData $current,
        public float $rainProbability,
        public float $outdoorScore,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'location' => $this->location->toArray(),
            'current' => $this->current->toArray(),
            'rainProbability' => $this->rainProbability,
            'outdoorScore' => $this->outdoorScore,
        ];
    }
}
