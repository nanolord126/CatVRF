<?php declare(strict_types=1);

namespace App\Domains\Taxi\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTaxiOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'           => ['nullable', 'integer', 'min:1'],
            'package_type'                => ['required', 'string', 'in:parcel,document,cargo,food'],
            'weight_kg'                   => ['sometimes', 'numeric', 'min:0.01', 'max:9999'],
            'recipient_name'              => ['required', 'string', 'min:2', 'max:255'],
            'recipient_phone'             => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'pickup_point'                => ['required', 'array'],
            'pickup_point.address'        => ['required', 'string', 'min:5', 'max:512'],
            'pickup_point.lat'            => ['required', 'numeric', 'between:-90,90'],
            'pickup_point.lon'            => ['required', 'numeric', 'between:-180,180'],
            'dropoff_point'               => ['required', 'array'],
            'dropoff_point.address'       => ['required', 'string', 'min:5', 'max:512'],
            'dropoff_point.lat'           => ['required', 'numeric', 'between:-90,90'],
            'dropoff_point.lon'           => ['required', 'numeric', 'between:-180,180'],
            'is_fragile'                  => ['sometimes', 'boolean'],
            'cash_on_delivery'            => ['sometimes', 'boolean'],
            'notes'                       => ['sometimes', 'string', 'max:500'],
            'tags'                        => ['sometimes', 'array'],
            'tags.*'                      => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'package_type.required'          => 'Тип посылки обязателен.',
            'recipient_name.required'        => 'Имя получателя обязательно.',
            'recipient_phone.required'       => 'Телефон получателя обязателен.',
            'pickup_point.address.required'  => 'Адрес отправления обязателен.',
            'dropoff_point.address.required' => 'Адрес доставки обязателен.',
        ];
    }
}
