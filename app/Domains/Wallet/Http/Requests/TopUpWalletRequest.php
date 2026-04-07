<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация операции пополнения кошелька.
 *
 * CANON CatVRF 2026 — Layer 4 (Requests).
 * final, authorize(), rules(), messages().
 */
final class TopUpWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'wallet_id'      => ['required', 'integer', 'min:1'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'type'           => ['required', 'string', 'in:deposit,bonus,refund'],
            'correlation_id' => ['required', 'string', 'uuid'],
            'metadata'       => ['nullable', 'json'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.min'           => 'Сумма пополнения должна быть > 0.',
            'amount.max'           => 'Сумма не может превышать 99 999 999.99.',
            'type.in'              => 'Тип должен быть: deposit, bonus, refund.',
            'correlation_id.uuid'  => 'Correlation ID должен быть UUID.',
        ];
    }
}
