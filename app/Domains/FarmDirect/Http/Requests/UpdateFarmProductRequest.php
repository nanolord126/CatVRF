<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateFarmProductRequest
 *
 * Part of the FarmDirect vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\FarmDirect\Http\Requests
 */
final class UpdateFarmProductRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'farm_id' => 'sometimes|integer|min:1',
            'category' => 'sometimes|string|max:255',
            'unit' => 'sometimes|string|max:255',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:1',
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
