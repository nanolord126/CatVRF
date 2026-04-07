<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания кошелька.
 *
 * CANON CatVRF 2026 — Layer 4 (Requests).
 * final, authorize(), rules(), messages().
 */
final class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tenant_id'         => ['required', 'integer', 'min:1'],
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'correlation_id'    => ['nullable', 'string', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID обязателен для создания кошелька.',
            'tenant_id.min'      => 'Tenant ID должен быть > 0.',
        ];
    }
}
