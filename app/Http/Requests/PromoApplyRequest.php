<?php declare(strict_types=1);

namespace App\Http\Requests;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PromoApplyRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests
 */
final class PromoApplyRequest extends FormRequest
{
    public function authorize(): bool
        {
            // CANON 2026: Fraud Check in FormRequest
            if ($this->guard->check()) {
                $correlationId = $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
                $fraudResult = app(\App\Services\FraudControlService::class)->check(
                    (int) $this->guard->id(),
                    'form_request',
                    (int) ($this->input('amount', 0)),
                    $this->ip(),
                    $this->header('X-Device-Fingerprint'),
                    $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    $this->logger->channel('fraud_alert')->warning('FormRequest blocked', [
                        'class'          => __CLASS__,
                        'correlation_id' => $correlationId,
                        'score'          => $fraudResult['score'],
                    ]);
                    return false;
                }
            }
            return $this->guard->check();
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
