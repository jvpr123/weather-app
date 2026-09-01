<?php

use App\Http\Controllers\Location\ReverseGeocodeController;
use App\Http\Controllers\Location\SearchLocationsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/locations/search', SearchLocationsController::class)
    ->name('locations.search');

Route::get('/locations/reverse', ReverseGeocodeController::class)
    ->name('locations.reverse');
