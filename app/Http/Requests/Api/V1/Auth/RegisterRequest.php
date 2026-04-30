<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'invite_code' => ['nullable', 'string', 'exists:tenant_invitations,token'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'fingerprint' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'in:mobile,desktop,tablet'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'correlation_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Пользователь с таким email уже существует',
            'phone.unique' => 'Пользователь с таким телефоном уже существует',
            'invite_code.exists' => 'Неверный код приглашения',
            'tenant_id.exists' => 'Тенант не найден',
        ];
    }
}
