<?php

namespace App\Contracts\Weather;

use App\DTOs\Location\Coordinates;
use App\DTOs\Weather\CurrentWeatherData;

interface CurrentWeatherProvider
{
    public function current(Coordinates $coordinates): CurrentWeatherData;
}
