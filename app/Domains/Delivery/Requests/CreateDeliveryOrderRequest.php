<?php declare(strict_types=1);

namespace App\Domains\Delivery\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'       => ['nullable', 'integer', 'min:1'],
            'package_type'            => ['required', 'string', 'in:small,medium,large,fragile,frozen'],
            'weight_kg'               => ['required', 'numeric', 'min:0.01', 'max:999'],
            'recipient_name'          => ['required', 'string', 'min:2', 'max:255'],
            'recipient_phone'         => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'pickup_point'            => ['required', 'array'],
            'pickup_point.address'    => ['required', 'string', 'max:512'],
            'pickup_point.lat'        => ['required', 'numeric', 'between:-90,90'],
            'pickup_point.lon'        => ['required', 'numeric', 'between:-180,180'],
            'dropoff_point'           => ['required', 'array'],
            'dropoff_point.address'   => ['required', 'string', 'max:512'],
            'dropoff_point.lat'       => ['required', 'numeric', 'between:-90,90'],
            'dropoff_point.lon'       => ['required', 'numeric', 'between:-180,180'],
            'notes'                   => ['sometimes', 'string', 'max:1000'],
            'tags'                    => ['sometimes', 'array'],
            'tags.*'                  => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'recipient_name.required'  => 'Имя получателя обязательно.',
            'recipient_phone.required' => 'Телефон получателя обязателен.',
            'pickup_point.required'    => 'Адрес отправки обязателен.',
            'dropoff_point.required'   => 'Адрес доставки обязателен.',
        ];
    }
}
