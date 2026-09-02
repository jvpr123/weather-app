<?php

namespace App\Http\Controllers\Weather;

use App\Actions\Weather\CompareCitiesAction;
use App\DTOs\Location\Coordinates;
use App\DTOs\Location\LocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weather\CompareCitiesRequest;
use Illuminate\Http\JsonResponse;

final class CompareCitiesController extends Controller
{
    public function __invoke(
        CompareCitiesRequest $request,
        CompareCitiesAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'data' => $action->execute(
                $this->location($validated['left']),
                $this->location($validated['right']),
            )->toArray(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function location(array $data): LocationData
    {
        return new LocationData(
            name: (string) $data['name'],
            state: isset($data['state']) ? (string) $data['state'] : null,
            country: (string) $data['country'],
            coordinates: new Coordinates(
                latitude: (float) $data['latitude'],
                longitude: (float) $data['longitude'],
            ),
        );
    }
}
