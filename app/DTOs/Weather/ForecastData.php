<?php

namespace App\DTOs\Weather;

final readonly class ForecastData
{
    /** @param list<ForecastPeriodData> $periods */
    public function __construct(
        public array $periods,
        public int $timezoneOffset,
    ) {}

    /** @return array{periods: list<array<string, float|int|string>>, timezoneOffset: int} */
    public function toArray(): array
    {
        return [
            'periods' => array_map(
                fn (ForecastPeriodData $period): array => $period->toArray(),
                $this->periods,
            ),
            'timezoneOffset' => $this->timezoneOffset,
        ];
    }
}
