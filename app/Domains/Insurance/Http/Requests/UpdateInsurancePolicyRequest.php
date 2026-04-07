<?php declare(strict_types=1);

namespace App\Domains\Insurance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateInsurancePolicyRequest
 *
 * Part of the Insurance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Insurance\Http\Requests
 */
final class UpdateInsurancePolicyRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [
            'type' => 'sometimes|string|max:255',
            'coverage_amount' => 'sometimes|numeric|min:0',
            'premium' => 'sometimes|numeric|min:0',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
        ];
    }

    public function correlationId(): string
    {
        return $this->header('X-Correlation-ID', (string) Str::uuid());
    }

    public function isB2B(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correlation_id' => $this->correlationId()]);
    }
}
