<?php declare(strict_types=1);

namespace App\Domains\Referral\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'    => ['nullable', 'integer', 'min:1'],
            'referral_code'        => ['required', 'string', 'min:4', 'max:32', 'alpha_dash'],
            'program_id'           => ['required', 'integer', 'min:1'],
            'reward_type'          => ['required', 'string', 'in:bonus,cashback,discount,gift'],
            'reward_value'         => ['required', 'numeric', 'min:0'],
            'max_uses'             => ['sometimes', 'integer', 'min:1'],
            'expires_at'           => ['sometimes', 'date', 'after:today'],
            'tags'                 => ['sometimes', 'array'],
            'tags.*'               => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'referral_code.required'  => 'Реферальный код обязателен.',
            'referral_code.alpha_dash' => 'Код может содержать только буквы, цифры, дефисы и подчёркивания.',
            'program_id.required'     => 'Программа обязательна.',
            'reward_type.required'    => 'Тип вознаграждения обязателен.',
            'reward_value.required'   => 'Размер вознаграждения обязателен.',
        ];
    }
}
