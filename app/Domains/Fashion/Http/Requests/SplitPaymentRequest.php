<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SplitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'split_config' => ['required', 'array'],
            'split_config.client_share' => ['required', 'numeric', 'min:0', 'max:1'],
            'split_config.brand_share' => ['required', 'numeric', 'min:0', 'max:1'],
            'split_config.marketplace_share' => ['required', 'numeric', 'min:0', 'max:1'],
            'is_b2b' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Order not found',
            'total_amount.required' => 'Total amount is required',
            'total_amount.min' => 'Total amount must be positive',
            'split_config.required' => 'Split configuration is required',
            'split_config.client_share.required' => 'Client share is required',
            'split_config.brand_share.required' => 'Brand share is required',
            'split_config.marketplace_share.required' => 'Marketplace share is required',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $splitConfig = $this->input('split_config');
            $total = ($splitConfig['client_share'] ?? 0) + ($splitConfig['brand_share'] ?? 0) + ($splitConfig['marketplace_share'] ?? 0);
            
            if (abs($total - 1.0) > 0.01) {
                $validator->errors()->add('split_config', 'Split shares must sum to 1.0 (100%)');
            }
        });
    }
}
