<?php declare(strict_types=1);

namespace App\Domains\Finances\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFinanceRecordRequest extends FormRequest
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
            'type'              => ['required', 'string', 'in:income,expense,transfer,tax,commission'],
            'amount'            => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'currency'          => ['required', 'string', 'size:3'],
            'category'          => ['required', 'string', 'max:128'],
            'description'       => ['sometimes', 'string', 'max:1000'],
            'occurred_at'       => ['required', 'date'],
            'reference_id'      => ['sometimes', 'string', 'max:128'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.required'        => 'Тип операции обязателен.',
            'amount.required'      => 'Сумма обязательна.',
            'currency.required'    => 'Валюта обязательна.',
            'currency.size'        => 'Код валюты должен быть 3 символа (ISO 4217).',
            'category.required'    => 'Категория обязательна.',
            'occurred_at.required' => 'Дата операции обязательна.',
        ];
    }
}
