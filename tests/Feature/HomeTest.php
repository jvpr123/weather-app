<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the WeatherLens home page', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->missing('dashboard')
            ->missing('apiKey')
        );
})->with([
    'root' => '/',
    'weather page' => '/weather',
]);
