<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVeganProductRequest extends FormRequest
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
            'brand'             => ['required', 'string', 'max:128'],
            'price'             => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'weight_grams'      => ['sometimes', 'integer', 'min:1'],
            'calories'          => ['sometimes', 'integer', 'min:0'],
            'proteins'          => ['sometimes', 'numeric', 'min:0'],
            'fats'              => ['sometimes', 'numeric', 'min:0'],
            'carbohydrates'     => ['sometimes', 'numeric', 'min:0'],
            'certifications'    => ['sometimes', 'array'],
            'certifications.*'  => ['string', 'in:vegan,organic,raw,halal,kosher,gluten_free'],
            'ingredients'       => ['required', 'array', 'min:1'],
            'ingredients.*'     => ['string', 'max:128'],
            'description'       => ['required', 'string', 'min:20', 'max:4000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'        => 'Название продукта обязательно.',
            'category.required'    => 'Категория обязательна.',
            'price.required'       => 'Цена обязательна.',
            'ingredients.required' => 'Состав продукта обязателен.',
            'description.required' => 'Описание обязательно.',
        ];
    }
}
