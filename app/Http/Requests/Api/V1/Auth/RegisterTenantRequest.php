<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'inn' => ['required', 'string', 'size:10', 'unique:tenants,inn'],
            'kpp' => ['nullable', 'string', 'size:9'],
            'ogrn' => ['nullable', 'string', 'size:15'],
            'legal_entity_type' => ['nullable', 'in:OOO,IP,AO'],
            'legal_address' => ['nullable', 'string', 'max:500'],
            'actual_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'timezone' => ['nullable', 'timezone'],
            'correlation_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'inn.size' => 'ИНН должен состоять из 10 цифр для юр. лица',
            'inn.unique' => 'Организация с таким ИНН уже зарегистрирована',
            'kpp.size' => 'КПП должен состоять из 9 цифр',
            'ogrn.size' => 'ОГРН должен состоять из 15 цифр',
        ];
    }
}
