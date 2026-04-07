<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'integer', 'min:1'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'description' => ['sometimes', 'string', 'max:250'],
            'success_url' => ['required', 'url'],
            'fail_url'    => ['required', 'url'],
            'hold'        => ['sometimes', 'boolean'],
            'recurring'   => ['sometimes', 'boolean'],
            'metadata'    => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма платежа обязательна',
            'amount.min'      => 'Сумма должна быть больше 0',
            'success_url.url' => 'Некорректный URL успешной оплаты',
            'fail_url.url'    => 'Некорректный URL неудачной оплаты',
        ];
    }
}
