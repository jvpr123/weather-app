<?php

namespace App\Actions\Location;

use App\Contracts\Weather\GeocodingProvider;
use App\DTOs\Location\LocationData;

final readonly class SearchLocationsAction
{
    public function __construct(
        private GeocodingProvider $geocoding,
    ) {}

    /** @return list<LocationData> */
    public function execute(string $query, int $limit = 5): array
    {
        return $this->geocoding->search(trim($query), $limit);
    }
}
