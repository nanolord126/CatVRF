<?php declare(strict_types=1);

namespace App\Domains\Food\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDishRequest extends FormRequest
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
            'restaurant_id'     => ['required', 'integer', 'min:1'],
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'description'       => ['sometimes', 'string', 'max:2000'],
            'price'             => ['required', 'numeric', 'min:0'],
            'weight_grams'      => ['required', 'integer', 'min:1', 'max:99999'],
            'calories'          => ['required', 'integer', 'min:0'],
            'proteins'          => ['sometimes', 'numeric', 'min:0'],
            'fats'              => ['sometimes', 'numeric', 'min:0'],
            'carbohydrates'     => ['sometimes', 'numeric', 'min:0'],
            'is_available'      => ['sometimes', 'boolean'],
            'modifiers'         => ['sometimes', 'array'],
            'image_url'         => ['sometimes', 'url', 'max:512'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'restaurant_id.required' => 'Ресторан обязателен.',
            'name.required'          => 'Название блюда обязательно.',
            'price.required'         => 'Цена обязательна.',
            'weight_grams.required'  => 'Вес обязателен.',
            'calories.required'      => 'Калорийность обязательна.',
        ];
    }
}
