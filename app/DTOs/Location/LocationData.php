<?php

namespace App\DTOs\Location;

final readonly class LocationData
{
    public function __construct(
        public string $name,
        public ?string $state,
        public string $country,
        public Coordinates $coordinates,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     state: string|null,
     *     country: string,
     *     latitude: float,
     *     longitude: float
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'state' => $this->state,
            'country' => $this->country,
            'latitude' => $this->coordinates->latitude,
            'longitude' => $this->coordinates->longitude,
        ];
    }
}
