<?php

namespace App\Http\Requests\Weather;

use Illuminate\Foundation\Http\FormRequest;

final class CompareCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'left.name' => ['bail', 'required', 'string', 'max:100'],
            'left.state' => ['bail', 'nullable', 'string', 'max:100'],
            'left.country' => ['bail', 'required', 'string', 'size:2'],
            'left.latitude' => ['bail', 'required', 'numeric', 'between:-90,90'],
            'left.longitude' => ['bail', 'required', 'numeric', 'between:-180,180'],
            'right.name' => ['bail', 'required', 'string', 'max:100'],
            'right.state' => ['bail', 'nullable', 'string', 'max:100'],
            'right.country' => ['bail', 'required', 'string', 'size:2'],
            'right.latitude' => ['bail', 'required', 'numeric', 'between:-90,90'],
            'right.longitude' => ['bail', 'required', 'numeric', 'between:-180,180'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['left', 'right'] as $side) {
            $location = $this->input($side);

            if (! is_array($location)) {
                continue;
            }

            $name = $location['name'] ?? null;
            $state = $location['state'] ?? null;
            $country = $location['country'] ?? null;

            $location['name'] = is_string($name) ? trim($name) : $name;
            $location['state'] = is_string($state) && trim($state) !== '' ? trim($state) : null;
            $location['country'] = is_string($country) ? strtoupper(trim($country)) : $country;

            $this->merge([$side => $location]);
        }
    }
}
