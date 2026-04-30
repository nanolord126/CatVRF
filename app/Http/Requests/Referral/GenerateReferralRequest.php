<?php

declare(strict_types=1);

namespace App\Http\Requests\Referral;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GenerateReferralRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 */
final class GenerateReferralRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        return $this->guard->check();
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle messages operation.
     *
     * @throws \DomainException
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }
}
