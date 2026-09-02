<?php

use App\DTOs\Location\Coordinates;

it('stores valid coordinates', function () {
    $coordinates = new Coordinates(-23.5505, -46.6333);

    expect($coordinates->latitude)->toBe(-23.5505)
        ->and($coordinates->longitude)->toBe(-46.6333);
});

it('accepts coordinate boundary values', function () {
    expect(new Coordinates(-90, -180))->toBeInstanceOf(Coordinates::class)
        ->and(new Coordinates(90, 180))->toBeInstanceOf(Coordinates::class);
});

it('rejects invalid latitudes', function (float $latitude) {
    expect(fn () => new Coordinates($latitude, 0))
        ->toThrow(InvalidArgumentException::class, 'Latitude');
})->with([-90.01, 90.01, -INF, INF, NAN]);

it('rejects invalid longitudes', function (float $longitude) {
    expect(fn () => new Coordinates(0, $longitude))
        ->toThrow(InvalidArgumentException::class, 'Longitude');
})->with([-180.01, 180.01, -INF, INF, NAN]);
