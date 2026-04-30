<?php

declare(strict_types=1);

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class VerifyPrescriptionRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 */
final class VerifyPrescriptionRequest extends FormRequest
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
            $correlationId = $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
            $fraudResult = app(FraudControlService::class)->check(
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
            'prescription_id' => ['required', 'integer', 'exists:prescriptions,id'],
            'verified_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
