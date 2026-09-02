<?php

use App\Services\Weather\OutdoorScoreCalculator;

it('gives a high score to comfortable dry and calm weather', function () {
    $score = (new OutdoorScoreCalculator)->calculate(
        temperature: 22,
        rainProbability: 0,
        humidity: 55,
        windSpeed: 2,
        condition: 'Clear',
    );

    expect($score)->toBe(10.0);
});

it('penalizes high temperature and humidity', function () {
    $score = (new OutdoorScoreCalculator)->calculate(
        temperature: 35,
        rainProbability: 0,
        humidity: 90,
        windSpeed: 2,
        condition: 'Clear',
    );

    expect($score)->toBe(6.0);
});

it('gives a low score when rain probability is ninety percent', function () {
    $score = (new OutdoorScoreCalculator)->calculate(
        temperature: 17,
        rainProbability: 0.9,
        humidity: 90,
        windSpeed: 9,
        condition: 'Rain',
    );

    expect($score)->toBe(4.3);
});

it('gives a very low score to severe thunderstorm conditions', function () {
    $score = (new OutdoorScoreCalculator)->calculate(
        temperature: 35,
        rainProbability: 0.95,
        humidity: 90,
        windSpeed: 15,
        condition: 'Thunderstorm',
    );

    expect($score)->toBe(0.8);
});

it('always keeps the score between zero and ten', function (
    float $temperature,
    float $rainProbability,
    int $humidity,
    float $windSpeed,
) {
    $score = (new OutdoorScoreCalculator)->calculate(
        $temperature,
        $rainProbability,
        $humidity,
        $windSpeed,
        'Unknown',
    );

    expect($score)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(10.0);
})->with([
    'extreme heat' => [100.0, 2.0, 200, 100.0],
    'extreme cold' => [-100.0, -1.0, -50, -10.0],
]);

it('keeps the ideal range boundaries at the maximum score', function (
    float $temperature,
    int $humidity,
    float $windSpeed,
) {
    expect((new OutdoorScoreCalculator)->calculate(
        temperature: $temperature,
        rainProbability: 0,
        humidity: $humidity,
        windSpeed: $windSpeed,
        condition: 'Clear',
    ))->toBe(10.0);
})->with([
    'lower boundaries' => [18.0, 40, 5.0],
    'upper boundaries' => [26.0, 70, 5.0],
]);

it('clamps rain probability before applying its weight', function (
    float $rainProbability,
    float $expectedScore,
) {
    expect((new OutdoorScoreCalculator)->calculate(
        temperature: 22,
        rainProbability: $rainProbability,
        humidity: 55,
        windSpeed: 2,
        condition: 'Clear',
    ))->toBe($expectedScore);
})->with([
    'below zero' => [-0.5, 10.0],
    'above one' => [1.5, 7.0],
]);

it('applies the configured condition contribution', function (
    string $condition,
    float $expectedScore,
) {
    expect((new OutdoorScoreCalculator)->calculate(
        temperature: 22,
        rainProbability: 0,
        humidity: 55,
        windSpeed: 2,
        condition: $condition,
    ))->toBe($expectedScore);
})->with([
    'clear' => ['Clear', 10.0],
    'clouds' => ['Clouds', 9.8],
    'drizzle' => ['Drizzle', 9.5],
    'rain' => ['Rain', 9.3],
    'thunderstorm' => ['Thunderstorm', 9.1],
    'unknown fallback' => ['Mist', 9.4],
]);
