<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFurnitureItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'material' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'dimensions' => 'nullable|string|max:200',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ];
    }
}
