<?php declare(strict_types=1);

namespace App\Domains\Travel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'        => ['nullable', 'integer', 'min:1'],
            'b2b_travel_storefront_id' => ['sometimes', 'integer', 'min:1'],
            'company_contact_person'   => ['required', 'string', 'min:2', 'max:255'],
            'company_phone'            => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.type'             => ['required', 'string', 'in:ticket,hotel,excursion,transfer,insurance'],
            'items.*.name'             => ['required', 'string', 'max:512'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.price'            => ['required', 'numeric', 'min:0'],
            'departure_date'           => ['required', 'date', 'after:today'],
            'return_date'              => ['required', 'date', 'after:departure_date'],
            'destination'              => ['required', 'string', 'max:255'],
            'travellers_count'         => ['required', 'integer', 'min:1'],
            'payment_method'           => ['required', 'string', 'in:card,sbp,wallet,credit,deferred'],
            'special_requirements'     => ['sometimes', 'string', 'max:1000'],
            'tags'                     => ['sometimes', 'array'],
            'tags.*'                   => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'company_contact_person.required' => 'Контактное лицо обязательно.',
            'company_phone.required'           => 'Телефон обязателен.',
            'items.required'                   => 'Состав заказа обязателен.',
            'departure_date.required'          => 'Дата отправления обязательна.',
            'return_date.required'             => 'Дата возвращения обязательна.',
            'destination.required'             => 'Направление обязательно.',
            'travellers_count.required'        => 'Количество путешественников обязательно.',
        ];
    }
}
