<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class Verify2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string', 'min:6', 'max:10'],
            'fingerprint' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'in:mobile,desktop,tablet'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'correlation_id' => ['nullable', 'uuid'],
        ];
    }
}
