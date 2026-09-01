<?php

use App\Integrations\OpenWeather\OpenWeatherProvider;

return [
    'provider' => env('WEATHER_PROVIDER', 'openweather'),

    'providers' => [
        'openweather' => [
            'driver' => OpenWeatherProvider::class,
            'api_key' => env('OPENWEATHER_API_KEY'),
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org'),
            'timeout' => (int) env('OPENWEATHER_TIMEOUT', 10),
            'connect_timeout' => (int) env('OPENWEATHER_CONNECT_TIMEOUT', 5),
            'retry_times' => (int) env('OPENWEATHER_RETRY_TIMES', 3),
            'retry_delay_ms' => (int) env('OPENWEATHER_RETRY_DELAY_MS', 200),
        ],
    ],
];
