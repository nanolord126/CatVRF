<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_amount' => ['required', 'numeric', 'min:0'],
            'reward_type' => ['nullable', 'string', 'in:purchase,review,referral,try_on,style_analysis'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Order not found',
            'order_amount.required' => 'Order amount is required',
            'order_amount.min' => 'Order amount must be positive',
            'reward_type.in' => 'Reward type must be one of: purchase, review, referral, try_on, style_analysis',
        ];
    }
}
