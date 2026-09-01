<?php

namespace App\Contracts\Weather;

use App\DTOs\Location\LocationData;

interface GeocodingProvider
{
    /** @return list<LocationData> */
    public function search(string $query, int $limit = 5): array;
}
