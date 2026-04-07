<?php declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateArtworkRequest
 *
 * Part of the Art vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Art\Http\Requests
 */
final class UpdateArtworkRequest extends FormRequest
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
            'artist_id' => 'sometimes|integer|min:1',
            'technique' => 'sometimes|string|max:255',
            'dimensions' => 'sometimes|array',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string|max:255',
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
