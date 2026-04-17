<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateApartmentRequest extends FormRequest
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
            'name'               => ['required', 'string', 'min:3', 'max:255'],
            'address'            => ['required', 'string', 'min:5', 'max:512'],
            'lat'                => ['required', 'numeric', 'between:-90,90'],
            'lon'                => ['required', 'numeric', 'between:-180,180'],
            'rooms'              => ['required', 'integer', 'min:0', 'max:99'],
            'area_sqm'           => ['required', 'numeric', 'min:1'],
            'floor'              => ['required', 'integer', 'min:-5', 'max:200'],
            'price_per_night'    => ['required', 'numeric', 'min:0'],
            'deposit_amount'     => ['sometimes', 'numeric', 'min:0'],
            'amenities'          => ['sometimes', 'array'],
            'amenities.*'        => ['string', 'in:wifi,parking,kitchen,washing_machine,air_conditioning,tv,balcony,pool'],
            'images'             => ['required', 'array', 'min:3', 'max:30'],
            'images.*'           => ['url', 'max:512'],
            'check_in_time'      => ['required', 'date_format:H:i'],
            'check_out_time'     => ['required', 'date_format:H:i'],
            'min_nights'         => ['required', 'integer', 'min:1'],
            'max_nights'         => ['sometimes', 'integer', 'gte:min_nights'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'           => 'Название обязательно.',
            'address.required'        => 'Адрес обязателен.',
            'price_per_night.required' => 'Цена за ночь обязательна.',
            'images.min'              => 'Минимум 3 фотографии.',
            'min_nights.required'     => 'Минимальный срок аренды обязателен.',
        ];
    }
}
