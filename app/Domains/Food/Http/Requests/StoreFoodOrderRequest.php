<?php

namespace App\Domains\Food\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => 'required|exists:restaurants,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ];
    }
}
