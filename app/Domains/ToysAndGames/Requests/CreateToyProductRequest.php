<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateToyProductRequest extends FormRequest
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
            'age_min_years'     => ['required', 'integer', 'min:0', 'max:99'],
            'age_max_years'     => ['sometimes', 'integer', 'gte:age_min_years', 'max:99'],
            'price'             => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'materials'         => ['sometimes', 'array'],
            'materials.*'       => ['string', 'max:64'],
            'safety_certificates' => ['sometimes', 'array'],
            'safety_certificates.*' => ['string', 'max:128'],
            'is_educational'    => ['sometimes', 'boolean'],
            'images'            => ['sometimes', 'array', 'max:15'],
            'images.*'          => ['url', 'max:512'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'         => 'Название товара обязательно.',
            'category.required'     => 'Категория обязательна.',
            'brand.required'        => 'Бренд обязателен.',
            'age_min_years.required' => 'Минимальный возраст обязателен.',
            'price.required'        => 'Цена обязательна.',
            'stock.required'        => 'Остаток обязателен.',
        ];
    }
}
