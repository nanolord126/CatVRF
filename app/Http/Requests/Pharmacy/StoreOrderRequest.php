<?php declare(strict_types=1);

namespace App\Http\Requests\Pharmacy;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreOrderRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Pharmacy
 */
final class StoreOrderRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
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
                'prescription_id' => ['sometimes', 'integer', 'exists:prescriptions,id'],
                'client_id' => ['required', 'integer', 'exists:users,id'],
                'medicines' => ['required', 'json'],
                'delivery_date' => ['required', 'date', 'after:today'],
            ];
        }
}
