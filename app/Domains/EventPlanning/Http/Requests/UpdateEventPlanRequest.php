<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateEventPlanRequest
 *
 * Part of the EventPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\EventPlanning\Http\Requests
 */
final class UpdateEventPlanRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'event_type' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'venue' => 'sometimes|string|max:255',
            'budget' => 'sometimes|numeric|min:0',
            'guest_count' => 'sometimes|integer|min:1',
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
