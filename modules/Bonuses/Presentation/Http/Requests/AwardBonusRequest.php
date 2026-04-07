<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AwardBonusRequest
 *
 * Implements natively cleanly structurally verified payload assertions strictly cleanly directly inherently properly securely seamlessly.
 */
final class AwardBonusRequest extends FormRequest
{
    /**
     * Determines implicitly natively structured bounds distinctly correctly validating correctly actively thoroughly fundamentally dynamically correctly.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines mapped uniquely bounded perfectly effective assertions dynamically locally strictly logically clearly natively implicitly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'type' => ['required', 'string', 'in:loyalty,referral,compensation,promotional'],
            'correlation_id' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
