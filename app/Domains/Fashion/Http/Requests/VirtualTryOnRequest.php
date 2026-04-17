<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VirtualTryOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'design_id' => ['required', 'integer', 'exists:user_ai_designs,id'],
            'product_ids' => ['required', 'array', 'min:1', 'max:10'],
            'product_ids.*' => ['integer', 'exists:fashion_products,id'],
            'is_b2b' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'design_id.required' => 'Design ID is required',
            'design_id.exists' => 'Design not found',
            'product_ids.required' => 'Product IDs are required',
            'product_ids.min' => 'At least 1 product is required',
            'product_ids.max' => 'Maximum 10 products allowed',
            'product_ids.*.exists' => 'One or more products not found',
        ];
    }
}
