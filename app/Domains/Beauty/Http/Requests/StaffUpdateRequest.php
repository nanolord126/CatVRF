<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StaffUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
