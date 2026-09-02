<?php

namespace App\DTOs\Weather;

final readonly class CityComparisonData
{
    /** @param 'left'|'right'|'tie' $recommendation */
    public function __construct(
        public ComparedCityData $left,
        public ComparedCityData $right,
        public string $recommendation,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'left' => $this->left->toArray(),
            'right' => $this->right->toArray(),
            'recommendation' => $this->recommendation,
        ];
    }
}
