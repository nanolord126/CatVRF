<?php

declare(strict_types=1);

namespace App\Domains\Auto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0.01',
            'mileage' => 'sometimes|integer|min:0',
            'status' => 'nullable|string|in:available,sold,maintenance,inactive',
        ];
    }
}
