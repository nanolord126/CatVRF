<?php
declare(strict_types=1);

namespace App\Http\Requests;

final class PromoApplyRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'promo_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/'],
            'cart_id' => ['nullable', 'integer'],
            'order_id' => ['nullable', 'integer'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'promo_code.required' => 'Promo code is required',
            'promo_code.max' => 'Promo code must not exceed 50 characters',
            'promo_code.regex' => 'Promo code must contain only uppercase letters, numbers, dashes and underscores',
            'cart_id.integer' => 'Cart ID must be an integer',
            'order_id.integer' => 'Order ID must be an integer',
        ];
    }
}
