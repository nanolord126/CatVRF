<?php

declare(strict_types=1);

namespace App\Domains\Apparel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateClothingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'sometimes|string|max:100',
            'brand' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
            'material' => 'nullable|string|max:100',
            'price' => 'sometimes|numeric|min:0.01|max:999999.99',
            'stock_quantity' => 'sometimes|integer|min:0|max:99999',
            'sku' => 'nullable|string|unique:clothings,sku,'.$this->route('apparel'),
            'images' => 'nullable|json',
            'status' => 'nullable|string|in:active,inactive,archived',
        ];
    }
}
