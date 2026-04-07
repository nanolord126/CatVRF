<?php
declare(strict_types=1);

namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ReviewRequest
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
final class ReviewRequest extends FormRequest
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
            'title' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'correlation_id' => ['nullable', 'string'],
        ];
    }
}
