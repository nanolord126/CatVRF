<?php

declare(strict_types=1);

namespace App\Domains\BeautyShop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBeautyProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:cosmetics,perfumery,skincare,makeup,haircare',
            'brand' => 'required|string|max:100',
            'type' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ];
    }
}
