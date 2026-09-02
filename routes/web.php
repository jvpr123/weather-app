<?php

use App\Http\Controllers\Location\ReverseGeocodeController;
use App\Http\Controllers\Location\SearchLocationsController;
use App\Http\Controllers\Weather\CompareCitiesController;
use App\Http\Controllers\Weather\GetWeatherDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/locations/search', SearchLocationsController::class)
    ->name('locations.search');

Route::get('/locations/reverse', ReverseGeocodeController::class)
    ->name('locations.reverse');

Route::get('/weather/dashboard', GetWeatherDashboardController::class)
    ->name('weather.dashboard');

Route::get('/weather/compare/results', CompareCitiesController::class)
    ->name('weather.compare.results');

Route::get('/weather/compare', function () {
    return Inertia::render('Weather/Compare');
})->name('weather.compare');
