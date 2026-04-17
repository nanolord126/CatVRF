<?php declare(strict_types=1);

namespace App\Domains\Luxury\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateLuxuryListingRequest extends FormRequest
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
            'category'          => ['required', 'string', 'in:jewelry,watches,fashion,accessories,art,real_estate,yachts,aircraft,cars'],
            'price'             => ['required', 'numeric', 'min:10000'],
            'currency'          => ['required', 'string', 'size:3'],
            'condition'         => ['required', 'string', 'in:new,like_new,excellent,good'],
            'authenticity'      => ['required', 'boolean'],
            'certificate_url'   => ['required_if:authenticity,true', 'url', 'max:512'],
            'description'       => ['required', 'string', 'min:50', 'max:10000'],
            'images'            => ['required', 'array', 'min:3', 'max:30'],
            'images.*'          => ['url', 'max:512'],
            'is_negotiable'     => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'brand_id.required'        => 'Бренд обязателен.',
            'name.required'            => 'Название обязательно.',
            'price.required'           => 'Цена обязательна.',
            'price.min'                => 'Минимальная цена для люкс-сегмента — 10 000.',
            'authenticity.required'    => 'Подлинность должна быть подтверждена.',
            'certificate_url.required_if' => 'Сертификат подлинности обязателен.',
            'images.min'               => 'Минимум 3 фотографии.',
        ];
    }
}
