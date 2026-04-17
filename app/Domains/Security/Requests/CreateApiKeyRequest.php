<?php declare(strict_types=1);

namespace App\Domains\Security\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create_api_keys');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['ip'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'API key name is required',
            'ip_whitelist.*.ip' => 'Invalid IP address in whitelist',
            'expires_at.after' => 'Expiration date must be in the future',
        ];
    }
}
