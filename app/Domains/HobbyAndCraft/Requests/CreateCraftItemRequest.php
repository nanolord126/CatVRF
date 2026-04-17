<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCraftItemRequest extends FormRequest
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
            'category'          => ['required', 'string', 'max:128'],
            'skill_level'       => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'price'             => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'materials'         => ['sometimes', 'array'],
            'materials.*'       => ['string', 'max:128'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'is_digital'        => ['sometimes', 'boolean'],
            'images'            => ['sometimes', 'array', 'max:10'],
            'images.*'          => ['url', 'max:512'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'        => 'Название обязательно.',
            'category.required'    => 'Категория обязательна.',
            'skill_level.required' => 'Уровень сложности обязателен.',
            'price.required'       => 'Цена обязательна.',
            'stock.required'       => 'Остаток обязателен.',
        ];
    }
}
