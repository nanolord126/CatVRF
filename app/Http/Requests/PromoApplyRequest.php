declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Requests;

final /**
 * PromoApplyRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PromoApplyRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (auth()->check()) {
            $correlationId = $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
            $fraudResult = app(\App\Services\FraudControlService::class)->check(
                (int) auth()->id(),
                'form_request',
                (int) ($this->input('amount', 0)),
                $this->ip(),
                $this->header('X-Device-Fingerprint'),
                $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                \Illuminate\Support\Facades\$this->log->channel('fraud_alert')->warning('FormRequest blocked', [
                    'class'          => __CLASS__,
                    'correlation_id' => $correlationId,
                    'score'          => $fraudResult['score'],
                ]);
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
