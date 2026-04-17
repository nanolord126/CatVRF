<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoyaltyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tiers' => ['sometimes', 'array'],
            'tiers.*.name' => ['string'],
            'tiers.*.threshold' => ['numeric'],
            'referral_bonus' => ['sometimes', 'numeric'],
            'birthday_bonus' => ['sometimes', 'numeric'],
        ];
    }
}
