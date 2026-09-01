<?php

namespace App\DTOs\Weather;

use App\DTOs\Location\LocationData;

final readonly class WeatherDashboardData
{
    /**
     * @param  list<ForecastPeriodData>  $hourly
     * @param  list<DailyForecastData>  $daily
     * @param  'clear-day'|'clear-night'|'cloudy-day'|'cloudy-night'|'rain-day'|'rain-night'  $theme
     */
    public function __construct(
        public LocationData $location,
        public CurrentWeatherData $current,
        public array $hourly,
        public array $daily,
        public int $timezoneOffset,
        public string $theme,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'location' => $this->location->toArray(),
            'current' => $this->current->toArray(),
            'hourly' => array_map(
                fn (ForecastPeriodData $period): array => $period->toArray(),
                $this->hourly,
            ),
            'daily' => array_map(
                fn (DailyForecastData $day): array => $day->toArray(),
                $this->daily,
            ),
            'timezoneOffset' => $this->timezoneOffset,
            'theme' => $this->theme,
        ];
    }
}
