<?php

declare(strict_types=1);

namespace App\Domains\RealEstateSales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSaleRequest extends FormRequest
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
            'price' => 'required|numeric|min:0.01',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area_sqm' => 'nullable|numeric|min:0.01',
            'area_hectares' => 'nullable|numeric|min:0.01',
            'year_built' => 'nullable|integer|min:1800|max:'.(date('Y') + 1),
            'condition' => 'nullable|string|in:excellent,good,fair,needs_work',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|string|in:for_sale,sold,off_market',
        ];
    }
}
