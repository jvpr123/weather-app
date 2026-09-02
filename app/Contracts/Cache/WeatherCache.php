<?php

namespace App\Contracts\Cache;

use Closure;

interface WeatherCache
{
    public function forget(string $key): bool;

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed;
}
