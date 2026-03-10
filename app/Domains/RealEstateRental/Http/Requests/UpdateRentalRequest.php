<?php

declare(strict_types=1);

namespace App\Domains\RealEstateRental\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rent_price' => 'sometimes|numeric|min:0.01',
            'status' => 'nullable|string|in:available,rented,maintenance',
            'available_from' => 'sometimes|date',
        ];
    }
}
