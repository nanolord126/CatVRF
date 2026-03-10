<?php

declare(strict_types=1);

namespace App\Domains\Apparel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreClothingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'brand' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
            'material' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock_quantity' => 'required|integer|min:0|max:99999',
            'sku' => 'nullable|string|unique:clothings',
            'images' => 'nullable|json',
            'status' => 'nullable|string|in:active,inactive,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно',
            'price.required' => 'Цена обязательна',
            'category.required' => 'Категория обязательна',
            'stock_quantity.required' => 'Количество на складе обязательно',
        ];
    }
}
