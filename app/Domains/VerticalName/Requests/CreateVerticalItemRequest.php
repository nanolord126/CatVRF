<?php declare(strict_types=1);

namespace App\Domains\VerticalName\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVerticalItemRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:2', 'max:512'],
            'description'       => ['required', 'string', 'min:10', 'max:10000'],
            'price'             => ['required', 'numeric', 'min:0'],
            'status'            => ['required', 'string', 'in:active,inactive,draft,archived'],
            'metadata'          => ['sometimes', 'array'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'        => 'Название обязательно.',
            'description.required' => 'Описание обязательно.',
            'price.required'       => 'Цена обязательна.',
            'status.required'      => 'Статус обязателен.',
        ];
    }
}
