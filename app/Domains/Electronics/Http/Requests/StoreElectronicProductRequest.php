<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreElectronicProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:2000',
            'specifications' => 'nullable|json',
            'warranty_months' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ];
    }
}
