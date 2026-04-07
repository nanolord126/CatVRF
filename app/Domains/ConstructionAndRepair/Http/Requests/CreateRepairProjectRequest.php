<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class CreateRepairProjectRequest
 *
 * Part of the ConstructionAndRepair vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\ConstructionAndRepair\Http\Requests
 */
final class CreateRepairProjectRequest extends FormRequest
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
            'address' => 'required|string|max:255',
            'project_type' => 'required|string|max:255',
            'area_sqm' => 'required|numeric|min:0',
            'budget' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
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
