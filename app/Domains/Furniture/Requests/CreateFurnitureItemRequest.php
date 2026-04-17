<?php declare(strict_types=1);

namespace App\Domains\Furniture\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFurnitureItemRequest extends FormRequest
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
            'category_id'       => ['required', 'integer', 'min:1'],
            'name'              => ['required', 'string', 'min:2', 'max:512'],
            'style'             => ['required', 'string', 'in:modern,classic,loft,scandinavian,minimalist,provence,boho'],
            'material'          => ['required', 'string', 'max:255'],
            'price'             => ['required', 'numeric', 'min:0'],
            'dimensions'        => ['required', 'array'],
            'dimensions.width'  => ['required', 'numeric', 'min:1'],
            'dimensions.height' => ['required', 'numeric', 'min:1'],
            'dimensions.depth'  => ['required', 'numeric', 'min:1'],
            'weight_kg'         => ['required', 'numeric', 'min:0.1'],
            'colors'            => ['required', 'array', 'min:1'],
            'colors.*'          => ['string', 'max:64'],
            'stock'             => ['required', 'integer', 'min:0'],
            'assembly_required' => ['sometimes', 'boolean'],
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
            'name.required'       => 'Название товара обязательно.',
            'style.required'      => 'Стиль обязателен.',
            'material.required'   => 'Материал обязателен.',
            'price.required'      => 'Цена обязательна.',
            'dimensions.required' => 'Габариты обязательны.',
            'weight_kg.required'  => 'Вес обязателен.',
            'stock.required'      => 'Остаток обязателен.',
        ];
    }
}
