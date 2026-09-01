<?php

namespace App\Http\Controllers\Location;

use App\Actions\Location\ReverseGeocodeAction;
use App\DTOs\Location\Coordinates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\ReverseGeocodeRequest;
use Illuminate\Http\JsonResponse;

final class ReverseGeocodeController extends Controller
{
    public function __invoke(
        ReverseGeocodeRequest $request,
        ReverseGeocodeAction $action,
    ): JsonResponse {
        $location = $action->execute(new Coordinates(
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
        ));

        return response()->json([
            'data' => $location?->toArray(),
        ]);
    }
}
