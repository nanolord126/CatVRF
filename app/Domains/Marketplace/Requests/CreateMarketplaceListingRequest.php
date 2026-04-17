<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMarketplaceListingRequest extends FormRequest
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
            'vertical'          => ['required', 'string', 'max:64'],
            'title'             => ['required', 'string', 'min:3', 'max:512'],
            'description'       => ['required', 'string', 'min:10', 'max:10000'],
            'price'             => ['required', 'numeric', 'min:0'],
            'category'          => ['required', 'string', 'max:128'],
            'condition'         => ['required', 'string', 'in:new,used,refurbished'],
            'images'            => ['sometimes', 'array', 'max:20'],
            'images.*'          => ['url', 'max:512'],
            'is_negotiable'     => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vertical.required'  => 'Вертикаль обязательна.',
            'title.required'     => 'Заголовок обязателен.',
            'description.required' => 'Описание обязательно.',
            'price.required'     => 'Цена обязательна.',
            'category.required'  => 'Категория обязательна.',
            'condition.required' => 'Состояние обязательно.',
        ];
    }
}
