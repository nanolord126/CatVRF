<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ARPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'design_id' => ['required', 'integer', 'exists:user_ai_designs,id'],
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'design_id.required' => 'Design ID is required',
            'design_id.exists' => 'Design not found',
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product not found',
        ];
    }
}
