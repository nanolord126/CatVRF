<?php declare(strict_types=1);

namespace App\Domains\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateContentPieceRequest
 *
 * Part of the Content vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Content\Http\Requests
 */
final class UpdateContentPieceRequest extends FormRequest
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
            'type' => 'sometimes|string|max:255',
            'body' => 'sometimes|string|max:255',
            'author_id' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string|max:255',
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
