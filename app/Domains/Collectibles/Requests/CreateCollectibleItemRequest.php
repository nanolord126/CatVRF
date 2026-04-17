<?php declare(strict_types=1);

namespace App\Domains\Collectibles\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCollectibleItemRequest extends FormRequest
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
            'condition'         => ['required', 'string', 'in:mint,excellent,good,fair,poor'],
            'year'              => ['sometimes', 'integer', 'min:1000', 'max:2100'],
            'price'             => ['required', 'numeric', 'min:0'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'images'            => ['sometimes', 'array', 'max:10'],
            'images.*'          => ['url', 'max:512'],
            'is_auction'        => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'      => 'Название предмета обязательно.',
            'category.required'  => 'Категория обязательна.',
            'condition.required' => 'Состояние обязательно.',
            'price.required'     => 'Цена обязательна.',
        ];
    }
}
