<?php declare(strict_types=1);

namespace App\Domains\Geo\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGeoLocationRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'type'              => ['required', 'string', 'in:zone,point,polygon,warehouse,store,pickup'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lon'               => ['required', 'numeric', 'between:-180,180'],
            'address'           => ['sometimes', 'string', 'max:512'],
            'radius_km'         => ['sometimes', 'numeric', 'min:0.1', 'max:500'],
            'polygon'           => ['sometimes', 'array'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Название локации обязательно.',
            'type.required' => 'Тип локации обязателен.',
            'lat.required'  => 'Широта обязательна.',
            'lon.required'  => 'Долгота обязательна.',
        ];
    }
}
