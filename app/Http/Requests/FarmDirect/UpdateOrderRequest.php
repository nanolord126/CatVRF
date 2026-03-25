declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Requests\FarmDirect;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * UpdateOrderRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdateOrderRequest extends FormRequest
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
            'delivery_date' => ['sometimes', 'date', 'after:today'],
            'delivery_address' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'regex:/^\\+?[0-9]{10,15}$/'],
        ];
    }
}
