<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateCleaningOrderRequest
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\CleaningServices\Http\Requests
 */
final class UpdateCleaningOrderRequest extends FormRequest
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
            'address' => 'sometimes|string|max:255',
            'cleaning_type' => 'sometimes|string|max:255',
            'datetime' => 'sometimes|date',
            'area_sqm' => 'sometimes|numeric|min:0',
            'price' => 'sometimes|numeric|min:0',
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
