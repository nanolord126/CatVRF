<?php

declare(strict_types=1);

namespace App\Domains\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления финансовой записи.
 *
 * CANON CatVRF 2026 — Layer 4 (Requests).
 * final, authorize(), rules(), messages().
 */
final class UpdateFinanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:255'],
            'type'              => ['sometimes', 'string', 'in:income,expense,transfer,payout,commission'],
            'amount'            => ['sometimes', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency'          => ['sometimes', 'string', 'size:3'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'tags'              => ['nullable', 'json'],
            'metadata'          => ['nullable', 'json'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.max'        => 'Название не может быть длиннее 255 символов.',
            'type.in'         => 'Тип должен быть: income, expense, transfer, payout, commission.',
            'amount.numeric'  => 'Сумма должна быть числом.',
            'amount.min'      => 'Сумма должна быть больше 0.',
            'amount.max'      => 'Сумма не может превышать 99 999 999.99.',
            'currency.size'   => 'Валюта должна быть ISO 4217 (3 символа).',
            'description.max' => 'Описание не может быть длиннее 1000 символов.',
            'tags.json'       => 'Теги должны быть валидным JSON.',
            'metadata.json'   => 'Метаданные должны быть валидным JSON.',
        ];
    }

    /**
     * Подготовка данных перед валидацией.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge([
                'currency' => strtoupper((string) $this->input('currency')),
            ]);
        }
    }

    /**
     * Человекочитаемые имена атрибутов.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'              => 'название',
            'type'              => 'тип',
            'amount'            => 'сумма',
            'currency'          => 'валюта',
            'description'       => 'описание',
            'business_group_id' => 'филиал',
            'tags'              => 'теги',
            'metadata'          => 'метаданные',
        ];
    }
}
