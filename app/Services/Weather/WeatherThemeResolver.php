<?php

namespace App\Services\Weather;

final readonly class WeatherThemeResolver
{
    /**
     * @return 'clear-day'|'clear-night'|'cloudy-day'|'cloudy-night'|'rain-day'|'rain-night'
     */
    public function resolve(
        string $condition,
        int $sunrise,
        int $sunset,
        int $timestamp,
    ): string {
        $period = $timestamp < $sunrise || $timestamp > $sunset
            ? 'night'
            : 'day';

        $weather = match ($condition) {
            'Clear' => 'clear',
            'Rain', 'Drizzle', 'Thunderstorm' => 'rain',
            default => 'cloudy',
        };

        return "{$weather}-{$period}";
    }
}
