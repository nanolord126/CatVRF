<?php

declare(strict_types=1);

namespace App\Domains\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления статуса платёжной записи.
 *
 * CANON CatVRF 2026 — Layer 4 (Requests).
 * final, authorize(), rules(), messages().
 */
final class UpdatePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'status'              => ['required', 'string', 'in:pending,authorized,captured,refunded,failed'],
            'provider_payment_id' => ['nullable', 'string', 'max:255'],
            'provider_response'   => ['nullable', 'json'],
            'correlation_id'      => ['nullable', 'string', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Статус платежа обязателен.',
            'status.in'       => 'Статус должен быть: pending, authorized, captured, refunded, failed.',
        ];
    }
}
