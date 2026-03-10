<?php

declare(strict_types=1);

namespace App\Domains\Auto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'vin' => 'nullable|string|unique:vehicles|max:50',
            'license_plate' => 'nullable|string|unique:vehicles|max:50',
            'price' => 'required|numeric|min:0.01',
            'mileage' => 'required|integer|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:available,sold,maintenance,inactive',
        ];
    }
}
