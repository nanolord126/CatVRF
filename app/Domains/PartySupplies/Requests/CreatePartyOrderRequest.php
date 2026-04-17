<?php declare(strict_types=1);

namespace App\Domains\PartySupplies\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePartyOrderRequest extends FormRequest
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
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'event_type'         => ['required', 'string', 'in:birthday,wedding,corporate,graduation,baby_shower,holiday'],
            'guests_count'       => ['required', 'integer', 'min:1'],
            'event_date'         => ['required', 'date', 'after_or_equal:today'],
            'delivery_address'   => ['required', 'string', 'min:5', 'max:512'],
            'theme'              => ['sometimes', 'string', 'max:128'],
            'color_palette'      => ['sometimes', 'array', 'max:5'],
            'color_palette.*'    => ['string', 'max:32'],
            'notes'              => ['sometimes', 'string', 'max:1000'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required'          => 'Состав заказа обязателен.',
            'event_type.required'     => 'Тип мероприятия обязателен.',
            'guests_count.required'   => 'Количество гостей обязательно.',
            'event_date.required'     => 'Дата мероприятия обязательна.',
            'delivery_address.required' => 'Адрес доставки обязателен.',
        ];
    }
}
