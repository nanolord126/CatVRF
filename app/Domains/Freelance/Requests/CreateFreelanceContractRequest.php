<?php declare(strict_types=1);

namespace App\Domains\Freelance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFreelanceContractRequest extends FormRequest
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
            'title'             => ['required', 'string', 'min:5', 'max:512'],
            'category'          => ['required', 'string', 'max:128'],
            'description'       => ['required', 'string', 'min:50', 'max:10000'],
            'price'             => ['required', 'numeric', 'min:0'],
            'price_type'        => ['required', 'string', 'in:fixed,hourly,milestone'],
            'deadline_days'     => ['required', 'integer', 'min:1', 'max:365'],
            'skills'            => ['required', 'array', 'min:1'],
            'skills.*'          => ['string', 'max:64'],
            'attachments'       => ['sometimes', 'array', 'max:10'],
            'attachments.*'     => ['url', 'max:512'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'       => 'Название проекта обязательно.',
            'description.required' => 'Описание обязательно.',
            'price.required'       => 'Бюджет обязателен.',
            'price_type.required'  => 'Тип оплаты обязателен.',
            'deadline_days.required' => 'Срок выполнения обязателен.',
            'skills.required'      => 'Навыки обязательны.',
        ];
    }
}
