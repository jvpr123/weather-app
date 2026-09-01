<?php

namespace App\Actions\Location;

use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;

final readonly class ReverseGeocodeAction
{
    public function __construct(
        private GeocodingProvider $geocoding,
    ) {}

    public function execute(Coordinates $coordinates): ?LocationData
    {
        return $this->geocoding->reverse($coordinates);
    }
}
