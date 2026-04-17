<?php declare(strict_types=1);

namespace App\Domains\Security\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RevokeApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('revoke_api_keys');
    }

    public function rules(): array
    {
        return [
            'key_id' => ['required', 'string', 'uuid'],
        ];
    }
}
