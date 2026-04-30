<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'fingerprint' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'in:mobile,desktop,tablet'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'correlation_id' => ['nullable', 'uuid'],
        ];
    }
}
