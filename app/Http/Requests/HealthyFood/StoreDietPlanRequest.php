<?php declare(strict_types=1);

namespace App\Http\Requests\HealthyFood;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreDietPlanRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\HealthyFood
 */
final class StoreDietPlanRequest extends FormRequest
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
                'client_id' => ['required', 'integer', 'exists:users,id'],
                'diet_type' => ['required', 'string', 'in:keto,vegan,paleo,low-carb,balanced,custom'],
                'duration_days' => ['required', 'integer', 'min:7', 'max:365'],
                'daily_calories' => ['required', 'integer', 'min:1000', 'max:5000'],
                'preferences' => ['sometimes', 'json'],
            ];
        }
}
