<?php

declare(strict_types=1);

namespace App\Domains\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания платёжной записи.
 *
 * CANON CatVRF 2026 — Layer 4 (Requests).
 * final, authorize(), rules(), messages().
 */
final class StorePaymentRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'idempotency_key'  => ['required', 'string', 'max:255'],
            'provider_code'    => ['required', 'string', 'in:tinkoff,sber,tochka,sbp'],
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency'         => ['sometimes', 'string', 'size:3'],
            'correlation_id'   => ['required', 'string', 'uuid'],
            'tenant_id'        => ['required', 'integer', 'min:1'],
            'metadata'         => ['nullable', 'json'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'Ключ идемпотентности обязателен.',
            'provider_code.in'         => 'Провайдер должен быть: tinkoff, sber, tochka, sbp.',
            'amount.min'               => 'Сумма платежа должна быть > 0.',
            'correlation_id.uuid'      => 'Correlation ID должен быть UUID.',
        ];
    }
}
