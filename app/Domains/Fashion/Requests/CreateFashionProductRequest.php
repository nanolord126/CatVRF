<?php declare(strict_types=1);

namespace App\Domains\Fashion\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFashionProductRequest extends FormRequest
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
            'brand_id'          => ['required', 'integer', 'min:1'],
            'name'              => ['required', 'string', 'min:2', 'max:512'],
            'category'          => ['required', 'string', 'max:128'],
            'price'             => ['required', 'numeric', 'min:0'],
            'gender'            => ['required', 'string', 'in:male,female,unisex,children'],
            'season'            => ['required', 'string', 'in:spring_summer,autumn_winter,all_season'],
            'sizes'             => ['required', 'array', 'min:1'],
            'sizes.*'           => ['string', 'max:16'],
            'colors'            => ['required', 'array', 'min:1'],
            'colors.*'          => ['string', 'max:64'],
            'material'          => ['sometimes', 'string', 'max:255'],
            'stock'             => ['required', 'integer', 'min:0'],
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
            'brand_id.required'  => 'Бренд обязателен.',
            'name.required'      => 'Название товара обязательно.',
            'price.required'     => 'Цена обязательна.',
            'gender.required'    => 'Пол обязателен.',
            'season.required'    => 'Сезон обязателен.',
            'sizes.required'     => 'Размеры обязательны.',
            'colors.required'    => 'Цвета обязательны.',
            'stock.required'     => 'Остаток обязателен.',
        ];
    }
}
