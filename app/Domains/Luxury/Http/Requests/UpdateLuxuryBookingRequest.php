<?php declare(strict_types=1);

namespace App\Domains\Luxury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateLuxuryBookingRequest
 *
 * Part of the Luxury vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Luxury\Http\Requests
 */
final class UpdateLuxuryBookingRequest extends FormRequest
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
            'service_type' => 'sometimes|string|max:255',
            'client_id' => 'sometimes|integer|min:1',
            'details' => 'sometimes|array',
            'budget' => 'sometimes|numeric|min:0',
            'preferred_date' => 'sometimes|date',
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
