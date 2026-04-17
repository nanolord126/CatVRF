<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StaffStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salon_id' => ['required', 'integer', 'exists:salons,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
