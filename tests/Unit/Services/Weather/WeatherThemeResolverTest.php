<?php

use App\Services\Weather\WeatherThemeResolver;

it('resolves a clear day at noon', function () {
    expect((new WeatherThemeResolver)->resolve(
        condition: 'Clear',
        sunrise: 600,
        sunset: 1800,
        timestamp: 1200,
    ))->toBe('clear-day');
});

it('resolves a clear night before sunrise', function () {
    expect((new WeatherThemeResolver)->resolve(
        condition: 'Clear',
        sunrise: 600,
        sunset: 1800,
        timestamp: 0,
    ))->toBe('clear-night');
});

it('resolves rain during the day', function () {
    expect((new WeatherThemeResolver)->resolve(
        condition: 'Rain',
        sunrise: 600,
        sunset: 1800,
        timestamp: 1200,
    ))->toBe('rain-day');
});

it('resolves clouds after sunset', function () {
    expect((new WeatherThemeResolver)->resolve(
        condition: 'Clouds',
        sunrise: 600,
        sunset: 1800,
        timestamp: 2300,
    ))->toBe('cloudy-night');
});

it('maps precipitation conditions to the rain theme', function (string $condition) {
    expect((new WeatherThemeResolver)->resolve($condition, 6, 18, 12))->toBe('rain-day');
})->with(['Rain', 'Drizzle', 'Thunderstorm']);

it('uses cloudy as the fallback theme', function (string $condition, int $timestamp, string $theme) {
    expect((new WeatherThemeResolver)->resolve($condition, 6, 18, $timestamp))->toBe($theme);
})->with([
    'unknown day' => ['Mist', 12, 'cloudy-day'],
    'unknown night' => ['Snow', 20, 'cloudy-night'],
]);

it('treats sunrise and sunset themselves as daytime', function (int $timestamp) {
    expect((new WeatherThemeResolver)->resolve('Clear', 6, 18, $timestamp))->toBe('clear-day');
})->with([6, 18]);
