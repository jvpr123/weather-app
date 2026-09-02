<?php

namespace App\Contracts\Cache;

use Closure;

interface WeatherCache
{
    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed;
}
