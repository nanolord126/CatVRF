<?php declare(strict_types=1);

namespace App\Domains\Sports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSportsProductRequest extends FormRequest
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
            'sport_type'        => ['required', 'string', 'max:128'],
            'brand'             => ['required', 'string', 'max:128'],
            'price'             => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'sizes'             => ['sometimes', 'array'],
            'sizes.*'           => ['string', 'max:16'],
            'gender'            => ['sometimes', 'string', 'in:male,female,unisex'],
            'images'            => ['sometimes', 'array', 'max:15'],
            'images.*'          => ['url', 'max:512'],
            'is_rental'         => ['sometimes', 'boolean'],
            'rental_price_day'  => ['required_if:is_rental,true', 'numeric', 'min:0'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'       => 'Название товара обязательно.',
            'category.required'   => 'Категория обязательна.',
            'sport_type.required' => 'Вид спорта обязателен.',
            'brand.required'      => 'Бренд обязателен.',
            'price.required'      => 'Цена обязательна.',
            'stock.required'      => 'Остаток обязателен.',
        ];
    }
}
