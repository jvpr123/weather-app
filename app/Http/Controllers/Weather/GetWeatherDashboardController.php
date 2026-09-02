<?php

namespace App\Http\Controllers\Weather;

use App\Actions\Weather\GetWeatherDashboardAction;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weather\WeatherDashboardRequest;
use Illuminate\Http\JsonResponse;

final class GetWeatherDashboardController extends Controller
{
    public function __invoke(
        WeatherDashboardRequest $request,
        GetWeatherDashboardAction $action,
    ): JsonResponse {
        $location = new LocationData(
            name: (string) $request->validated('name'),
            state: $request->validated('state'),
            country: (string) $request->validated('country'),
            coordinates: new Coordinates(
                latitude: (float) $request->validated('latitude'),
                longitude: (float) $request->validated('longitude'),
            ),
        );

        return response()->json([
            'data' => $action->execute($location, $request->boolean('refresh'))->toArray(),
        ]);
    }
}
