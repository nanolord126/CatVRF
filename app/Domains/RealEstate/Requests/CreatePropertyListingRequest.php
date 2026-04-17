<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePropertyListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'  => ['nullable', 'integer', 'min:1'],
            'type'               => ['required', 'string', 'in:apartment,house,commercial,land,garage,storage'],
            'deal_type'          => ['required', 'string', 'in:sale,rent,daily_rent'],
            'address'            => ['required', 'string', 'min:5', 'max:512'],
            'lat'                => ['required', 'numeric', 'between:-90,90'],
            'lon'                => ['required', 'numeric', 'between:-180,180'],
            'area_sqm'           => ['required', 'numeric', 'min:1'],
            'rooms'              => ['sometimes', 'integer', 'min:0', 'max:99'],
            'floor'              => ['sometimes', 'integer', 'min:-5', 'max:200'],
            'floors_total'       => ['sometimes', 'integer', 'min:1', 'max:200'],
            'price'              => ['required', 'numeric', 'min:0'],
            'description'        => ['required', 'string', 'min:20', 'max:10000'],
            'images'             => ['required', 'array', 'min:3', 'max:50'],
            'images.*'           => ['url', 'max:512'],
            'features'           => ['sometimes', 'array'],
            'features.*'         => ['string', 'in:balcony,parking,elevator,security,furniture,appliances'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.required'        => 'Тип недвижимости обязателен.',
            'deal_type.required'   => 'Тип сделки обязателен.',
            'address.required'     => 'Адрес обязателен.',
            'area_sqm.required'    => 'Площадь обязательна.',
            'price.required'       => 'Цена обязательна.',
            'description.required' => 'Описание обязательно.',
            'images.min'           => 'Минимум 3 фотографии.',
        ];
    }
}
