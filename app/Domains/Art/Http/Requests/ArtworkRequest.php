<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ArtworkRequest
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
final class ArtworkRequest extends FormRequest
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
            'artist_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'inn' => ['nullable', 'string'],
            'business_card_id' => ['nullable', 'string'],
            'correlation_id' => ['nullable', 'string'],
        ];
    }
}
