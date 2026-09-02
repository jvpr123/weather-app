<?php

arch('controllers do not depend on weather infrastructure or the HTTP client')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'App\Integrations',
        'Illuminate\Support\Facades\Http',
    ]);

arch('weather contracts do not depend on infrastructure')
    ->expect('App\Contracts\Weather')
    ->not->toUse('App\Integrations');

arch('cache contracts do not depend on infrastructure')
    ->expect('App\Contracts\Cache')
    ->not->toUse('App\Integrations');

arch('actions depend on abstractions instead of weather infrastructure')
    ->expect('App\Actions')
    ->not->toUse([
        'App\Integrations',
        'Illuminate\Support\Facades\Http',
    ]);
