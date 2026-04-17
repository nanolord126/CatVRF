<?php declare(strict_types=1);

namespace App\Domains\GeoLogistics\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateLogisticsRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'     => ['nullable', 'integer', 'min:1'],
            'origin_address'        => ['required', 'string', 'min:5', 'max:512'],
            'origin_lat'            => ['required', 'numeric', 'between:-90,90'],
            'origin_lon'            => ['required', 'numeric', 'between:-180,180'],
            'destination_address'   => ['required', 'string', 'min:5', 'max:512'],
            'destination_lat'       => ['required', 'numeric', 'between:-90,90'],
            'destination_lon'       => ['required', 'numeric', 'between:-180,180'],
            'transport_type'        => ['required', 'string', 'in:car,truck,train,air,sea'],
            'cargo_type'            => ['sometimes', 'string', 'max:128'],
            'weight_kg'             => ['required', 'numeric', 'min:0.01'],
            'volume_m3'             => ['sometimes', 'numeric', 'min:0.001'],
            'scheduled_pickup_at'   => ['required', 'date', 'after_or_equal:today'],
            'tags'                  => ['sometimes', 'array'],
            'tags.*'                => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'origin_address.required'      => 'Адрес отправления обязателен.',
            'destination_address.required' => 'Адрес назначения обязателен.',
            'transport_type.required'      => 'Тип транспорта обязателен.',
            'weight_kg.required'           => 'Вес груза обязателен.',
        ];
    }
}
