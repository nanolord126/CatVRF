<?php declare(strict_types=1);

namespace App\Domains\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'  => ['nullable', 'integer', 'min:1'],
            'amount'             => ['required', 'numeric', 'min:1', 'max:999999999'],
            'currency'           => ['required', 'string', 'size:3'],
            'provider'           => ['required', 'string', 'in:tinkoff,sber,tochka,sbp,yookassa'],
            'order_id'           => ['required', 'integer', 'min:1'],
            'order_type'         => ['required', 'string', 'max:128'],
            'idempotency_key'    => ['required', 'string', 'max:64'],
            'return_url'         => ['required', 'url', 'max:512'],
            'description'        => ['sometimes', 'string', 'max:255'],
            'metadata'           => ['sometimes', 'array'],
            'is_hold'            => ['sometimes', 'boolean'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.required'          => 'Сумма платежа обязательна.',
            'currency.required'        => 'Валюта обязательна.',
            'provider.required'        => 'Провайдер платежей обязателен.',
            'order_id.required'        => 'Заказ обязателен.',
            'idempotency_key.required' => 'Ключ идемпотентности обязателен.',
            'return_url.required'      => 'URL возврата обязателен.',
        ];
    }
}
