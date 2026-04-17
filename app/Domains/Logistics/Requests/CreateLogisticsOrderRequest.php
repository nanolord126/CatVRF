<?php declare(strict_types=1);

namespace App\Domains\Logistics\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateLogisticsOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'   => ['nullable', 'integer', 'min:1'],
            'origin_warehouse_id' => ['required', 'integer', 'min:1'],
            'destination_address' => ['required', 'string', 'min:5', 'max:512'],
            'destination_lat'     => ['required', 'numeric', 'between:-90,90'],
            'destination_lon'     => ['required', 'numeric', 'between:-180,180'],
            'cargo_type'          => ['required', 'string', 'in:general,fragile,frozen,hazardous,oversized,valuable'],
            'weight_kg'           => ['required', 'numeric', 'min:0.01'],
            'volume_m3'           => ['required', 'numeric', 'min:0.001'],
            'transport_type'      => ['required', 'string', 'in:car,truck,train,air,sea'],
            'scheduled_pickup_at' => ['required', 'date', 'after_or_equal:today'],
            'recipient_name'      => ['required', 'string', 'max:255'],
            'recipient_phone'     => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'insurance_required'  => ['sometimes', 'boolean'],
            'tags'                => ['sometimes', 'array'],
            'tags.*'              => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'origin_warehouse_id.required'  => 'Склад отправки обязателен.',
            'destination_address.required'  => 'Адрес назначения обязателен.',
            'cargo_type.required'           => 'Тип груза обязателен.',
            'weight_kg.required'            => 'Вес обязателен.',
            'transport_type.required'       => 'Тип транспорта обязателен.',
            'recipient_name.required'       => 'Имя получателя обязательно.',
        ];
    }
}
