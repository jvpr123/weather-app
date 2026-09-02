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
