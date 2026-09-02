<?php

use App\Http\Controllers\Location\ReverseGeocodeController;
use App\Http\Controllers\Location\SearchLocationsController;
use App\Http\Controllers\Weather\CompareCitiesController;
use App\Http\Controllers\Weather\GetWeatherDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::group(['prefix' => 'weather'], function () {
    Route::get('/', fn () => Inertia::render('Home'))->name('weather');
    Route::get('/compare', fn () => Inertia::render('Weather/Compare'))->name('weather.compare');

    Route::get('/dashboard', GetWeatherDashboardController::class)->name('weather.dashboard');
    Route::get('/compare/results', CompareCitiesController::class)->name('weather.compare.results');
});

Route::group(['prefix' => 'locations'], function () {
    Route::get('/search', SearchLocationsController::class)->name('locations.search');
    Route::get('/reverse', ReverseGeocodeController::class)->name('locations.reverse');
});
