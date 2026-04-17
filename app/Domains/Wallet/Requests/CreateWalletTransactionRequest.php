<?php declare(strict_types=1);

namespace App\Domains\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWalletTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'wallet_id'         => ['required', 'integer', 'min:1'],
            'type'              => ['required', 'string', 'in:deposit,withdrawal,commission,bonus,refund,payout,hold,release_hold'],
            'amount'            => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'currency'          => ['sometimes', 'string', 'size:3'],
            'description'       => ['required', 'string', 'min:3', 'max:255'],
            'idempotency_key'   => ['required', 'string', 'min:8', 'max:128'],
            'metadata'          => ['sometimes', 'array'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'wallet_id.required'       => 'Кошелёк обязателен.',
            'type.required'            => 'Тип операции обязателен.',
            'amount.required'          => 'Сумма обязательна.',
            'amount.min'               => 'Сумма должна быть положительной.',
            'description.required'     => 'Описание операции обязательно.',
            'idempotency_key.required' => 'Ключ идемпотентности обязателен.',
        ];
    }
}
