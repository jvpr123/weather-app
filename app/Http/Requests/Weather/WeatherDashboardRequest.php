<?php

namespace App\Http\Requests\Weather;

use Illuminate\Foundation\Http\FormRequest;

final class WeatherDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:100'],
            'state' => ['bail', 'nullable', 'string', 'max:100'],
            'country' => ['bail', 'required', 'string', 'size:2'],
            'latitude' => ['bail', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['bail', 'required', 'numeric', 'between:-180,180'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $state = $this->input('state');
        $country = $this->input('country');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'state' => is_string($state) && trim($state) !== '' ? trim($state) : null,
            'country' => is_string($country) ? strtoupper(trim($country)) : $country,
        ]);
    }
}
