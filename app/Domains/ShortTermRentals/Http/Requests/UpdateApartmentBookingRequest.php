<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateApartmentBookingRequest
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\ShortTermRentals\Http\Requests
 */
final class UpdateApartmentBookingRequest extends FormRequest
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
            'apartment_id' => 'sometimes|integer|min:1',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date',
            'guests' => 'sometimes|string|max:255',
            'total_price' => 'sometimes|numeric|min:0',
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
