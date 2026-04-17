<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWeddingBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'    => ['nullable', 'integer', 'min:1'],
            'organizer_id'         => ['required', 'integer', 'min:1'],
            'wedding_date'         => ['required', 'date', 'after:today'],
            'venue_address'        => ['required', 'string', 'min:5', 'max:512'],
            'guests_count'         => ['required', 'integer', 'min:1', 'max:9999'],
            'budget'               => ['required', 'numeric', 'min:0'],
            'services'             => ['required', 'array', 'min:1'],
            'services.*'           => ['string', 'in:ceremony,banquet,decor,photo,video,music,floristry,fireworks,transport,printing'],
            'theme'                => ['sometimes', 'string', 'max:255'],
            'color_palette'        => ['sometimes', 'array', 'max:6'],
            'color_palette.*'      => ['string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'prepayment_amount'    => ['required', 'numeric', 'min:0'],
            'client_name'          => ['required', 'string', 'min:2', 'max:255'],
            'client_phone'         => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'special_requirements' => ['sometimes', 'string', 'max:4000'],
            'tags'                 => ['sometimes', 'array'],
            'tags.*'               => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'organizer_id.required'     => 'Организатор обязателен.',
            'wedding_date.required'     => 'Дата свадьбы обязательна.',
            'venue_address.required'    => 'Адрес мероприятия обязателен.',
            'guests_count.required'     => 'Количество гостей обязательно.',
            'budget.required'           => 'Бюджет обязателен.',
            'services.required'         => 'Выберите хотя бы одну услугу.',
            'prepayment_amount.required' => 'Предоплата обязательна.',
            'client_name.required'      => 'Имя клиента обязательно.',
            'client_phone.required'     => 'Телефон клиента обязателен.',
        ];
    }
}
