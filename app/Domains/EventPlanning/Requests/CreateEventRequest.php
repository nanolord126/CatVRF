<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateEventRequest extends FormRequest
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
            'title'             => ['required', 'string', 'min:3', 'max:512'],
            'type'              => ['required', 'string', 'in:wedding,corporate,birthday,conference,concert,exhibition,private'],
            'venue_address'     => ['required', 'string', 'min:5', 'max:512'],
            'guests_count'      => ['required', 'integer', 'min:1', 'max:100000'],
            'budget'            => ['required', 'numeric', 'min:0'],
            'starts_at'         => ['required', 'date', 'after_or_equal:today'],
            'ends_at'           => ['required', 'date', 'after:starts_at'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'services'          => ['sometimes', 'array'],
            'services.*'        => ['string', 'in:catering,decoration,music,photography,lighting,mc,security'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'        => 'Название мероприятия обязательно.',
            'type.required'         => 'Тип мероприятия обязателен.',
            'venue_address.required' => 'Адрес площадки обязателен.',
            'guests_count.required' => 'Количество гостей обязательно.',
            'budget.required'       => 'Бюджет обязателен.',
            'starts_at.required'    => 'Дата начала обязательна.',
        ];
    }
}
