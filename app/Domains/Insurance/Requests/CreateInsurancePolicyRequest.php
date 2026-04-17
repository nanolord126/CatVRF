<?php declare(strict_types=1);

namespace App\Domains\Insurance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'   => ['nullable', 'integer', 'min:1'],
            'company_id'          => ['required', 'integer', 'min:1'],
            'type'                => ['required', 'string', 'in:health,life,property,auto,travel,liability'],
            'insured_name'        => ['required', 'string', 'min:2', 'max:255'],
            'insured_birthday'    => ['required', 'date', 'before:today'],
            'coverage_amount'     => ['required', 'numeric', 'min:1000'],
            'premium_amount'      => ['required', 'numeric', 'min:0'],
            'starts_at'           => ['required', 'date', 'after_or_equal:today'],
            'ends_at'             => ['required', 'date', 'after:starts_at'],
            'payment_frequency'   => ['required', 'string', 'in:monthly,quarterly,annual,single'],
            'tags'                => ['sometimes', 'array'],
            'tags.*'              => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'company_id.required'        => 'Страховая компания обязательна.',
            'type.required'              => 'Тип страхования обязателен.',
            'insured_name.required'      => 'ФИО застрахованного обязательно.',
            'coverage_amount.required'   => 'Страховая сумма обязательна.',
            'premium_amount.required'    => 'Страховая премия обязательна.',
            'payment_frequency.required' => 'Периодичность платежей обязательна.',
        ];
    }
}
