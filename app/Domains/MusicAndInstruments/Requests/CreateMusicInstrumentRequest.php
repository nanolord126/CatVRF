<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMusicInstrumentRequest extends FormRequest
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
            'type'              => ['required', 'string', 'in:strings,wind,percussion,keyboard,electronic,vocal'],
            'brand'             => ['required', 'string', 'max:128'],
            'model'             => ['required', 'string', 'max:128'],
            'condition'         => ['required', 'string', 'in:new,excellent,good,fair'],
            'price'             => ['required', 'numeric', 'min:0'],
            'is_rental'         => ['sometimes', 'boolean'],
            'rental_price_day'  => ['required_if:is_rental,true', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'description'       => ['sometimes', 'string', 'max:4000'],
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
            'name.required'      => 'Название инструмента обязательно.',
            'type.required'      => 'Тип инструмента обязателен.',
            'brand.required'     => 'Бренд обязателен.',
            'condition.required' => 'Состояние обязательно.',
            'price.required'     => 'Цена обязательна.',
        ];
    }
}
