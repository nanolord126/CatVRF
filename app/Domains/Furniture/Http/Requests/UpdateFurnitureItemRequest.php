<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateFurnitureItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0.01|max:999999.99',
            'stock' => 'sometimes|integer|min:0',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ];
    }
}
