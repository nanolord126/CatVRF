declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Requests\AutoParts;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * StoreOrderRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class StoreOrderRequest extends FormRequest
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
            'part_id' => ['required', 'integer', 'exists:auto_part_items,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'vin' => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.regex' => 'Invalid VIN format (17 characters, no I, O, Q)',
        ];
    }
}
