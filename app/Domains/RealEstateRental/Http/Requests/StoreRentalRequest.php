<?php

declare(strict_types=1);

namespace App\Domains\RealEstateRental\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:residential,commercial,land,enterprise',
            'address' => 'required|string|max:500',
            'rent_price' => 'required|numeric|min:0.01',
            'deposit' => 'required|numeric|min:0',
            'lease_term_months' => 'required|integer|min:1',
            'available_from' => 'required|date',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area_sqm' => 'nullable|numeric|min:0.01',
            'area_hectares' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|string|in:available,rented,maintenance',
        ];
    }
}
