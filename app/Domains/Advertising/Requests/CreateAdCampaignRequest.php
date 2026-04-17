<?php declare(strict_types=1);

namespace App\Domains\Advertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdCampaignRequest extends FormRequest
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
            'name'               => ['required', 'string', 'min:2', 'max:255'],
            'type'               => ['required', 'string', 'in:banner,email,push,shorts,native'],
            'budget'             => ['required', 'numeric', 'min:0', 'max:99999999'],
            'targeting'          => ['sometimes', 'array'],
            'targeting.vertical' => ['sometimes', 'string', 'max:64'],
            'targeting.geo'      => ['sometimes', 'string', 'max:128'],
            'starts_at'          => ['required', 'date', 'after_or_equal:today'],
            'ends_at'            => ['required', 'date', 'after:starts_at'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'    => 'Название кампании обязательно.',
            'budget.required'  => 'Бюджет кампании обязателен.',
            'starts_at.required' => 'Дата начала обязательна.',
            'ends_at.after'    => 'Дата окончания должна быть позже даты начала.',
        ];
    }
}
