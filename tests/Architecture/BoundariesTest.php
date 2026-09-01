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
