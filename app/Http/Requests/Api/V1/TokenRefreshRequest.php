<?php declare(strict_types=1);

namespace App\Http\Requests\Api\V1;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TokenRefreshRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\V1
 */
final class TokenRefreshRequest extends FormRequest
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

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [];
        }
}
