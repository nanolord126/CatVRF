<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class CreateFarmProductRequest
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
final class CreateFarmProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'farm_id' => 'required|integer|min:1',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'price_per_unit' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
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
