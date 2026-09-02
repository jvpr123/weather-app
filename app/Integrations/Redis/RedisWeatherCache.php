<?php

namespace App\Integrations\Redis;

use App\Contracts\Cache\WeatherCache;
use Closure;
use Illuminate\Contracts\Cache\Repository;

final readonly class RedisWeatherCache implements WeatherCache
{
    public function __construct(
        private Repository $repository,
    ) {}

    public function forget(string $key): bool
    {
        return $this->repository->forget($key);
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->repository->remember($key, max(1, $ttl), $callback);
    }
}
