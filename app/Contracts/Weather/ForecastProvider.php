<?php

namespace App\Contracts\Weather;

use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\ForecastData;

interface ForecastProvider
{
    public function forecast(Coordinates $coordinates, bool $forceRefresh = false): ForecastData;
}
