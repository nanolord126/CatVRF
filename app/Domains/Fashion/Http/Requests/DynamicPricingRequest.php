<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DynamicPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'is_b2b' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product not found',
        ];
    }
}
