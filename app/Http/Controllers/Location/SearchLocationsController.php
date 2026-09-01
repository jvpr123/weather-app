<?php

namespace App\Http\Controllers\Location;

use App\Actions\Location\SearchLocationsAction;
use App\DTOs\Location\LocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\SearchLocationRequest;
use Illuminate\Http\JsonResponse;

final class SearchLocationsController extends Controller
{
    public function __invoke(
        SearchLocationRequest $request,
        SearchLocationsAction $action,
    ): JsonResponse {
        $locations = $action->execute((string) $request->validated('q'));

        return response()->json([
            'data' => array_map(
                fn (LocationData $location): array => $location->toArray(),
                $locations,
            ),
        ]);
    }
}
