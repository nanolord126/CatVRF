<?php declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePromoCampaignRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:3', 'max:255'],
            'type'              => ['required', 'string', 'in:discount,cashback,gift,free_shipping,bundle,loyalty'],
            'discount_percent'  => ['required_if:type,discount', 'numeric', 'between:1,100'],
            'cashback_percent'  => ['required_if:type,cashback', 'numeric', 'between:1,100'],
            'conditions'        => ['sometimes', 'array'],
            'min_order_amount'  => ['sometimes', 'numeric', 'min:0'],
            'max_uses'          => ['sometimes', 'integer', 'min:1'],
            'max_uses_per_user' => ['sometimes', 'integer', 'min:1'],
            'starts_at'         => ['required', 'date', 'after_or_equal:today'],
            'ends_at'           => ['required', 'date', 'after:starts_at'],
            'verticals'         => ['sometimes', 'array'],
            'verticals.*'       => ['string', 'max:64'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'       => 'Название акции обязательно.',
            'type.required'       => 'Тип акции обязателен.',
            'starts_at.required'  => 'Дата начала обязательна.',
            'ends_at.required'    => 'Дата окончания обязательна.',
            'ends_at.after'       => 'Дата окончания должна быть после даты начала.',
        ];
    }
}
